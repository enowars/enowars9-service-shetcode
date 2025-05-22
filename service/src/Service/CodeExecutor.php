<?php

namespace App\Service;

use App\Entity\Problem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CodeExecutor
{
    
    private array $bannedPatterns = [
        '/import\s+os/', 
        '/from\s+os\s+import/', 
        '/import\s+subprocess/', 
        '/from\s+subprocess\s+import/',
        '/import\s+commands/',
        '/from\s+commands\s+import/',
        '/import\s+pty/',
        '/from\s+pty\s+import/',
        '/import\s+pexpect/',
        '/from\s+pexpect\s+import/',
        '/import\s+glob/',
        '/from\s+glob\s+import/',
        '/import\s+shutil/',
        '/from\s+shutil\s+import/',
        '/import\s+pathlib/',
        '/from\s+pathlib\s+import/',
        '/\W__import__\s*\(/',
        '/\Weval\s*\(/',
        '/\Wexec\s*\(/',
        '/\Wcompile\s*\(/',
        '/open\s*\(.+,\s*[\'"]w[\'"]/',
        '/open\s*\(.+,\s*[\'"]a[\'"]/',
        '/\.system\s*\(/',
        '/\.popen\s*\(/',
        '/\.call\s*\(/',
        '/\.Popen\s*\(/',
        '/\.run\s*\(/'
    ];

    public function executeUserCode(string $code, Problem $problem, mixed $userId): array
    {
        if (!$this->validateCode($code)) {
            return [
                [
                    'input' => $problem->getTestCases()[0],
                    'expected' => $problem->getExpectedOutputs()[0],
                    'output' => 'Security violation: Prohibited functions or imports detected',
                    'passed' => false,
                    'error' => 'Security violation: Prohibited functions or imports detected'
                ]
            ];
        }
        
        $maxRuntime = min($problem->getMaxRuntime(), 1);
        $results = [];
        $submissionsRoot = getcwd() . '/submissions';
        $userProblemDir = "$submissionsRoot/$userId/{$problem->getId()}";

        if (!is_dir($userProblemDir)) {
            mkdir($userProblemDir, 0777, true);
        }

        $securityWrapper = <<<PYTHON
import sys
import builtins
import importlib.util

BANNED_MODULES = ['os', 'subprocess', 'pty', 'pexpect', 'commands', 'popen', 
                  'shutil', 'system', 'glob', 'pathlib']

original_import = builtins.__import__

def secure_import(name, *args, **kwargs):
    if name in BANNED_MODULES or any(name.startswith(f"{mod}.") for mod in BANNED_MODULES):
        raise ImportError(f"Import of {name} is not allowed for security reasons")
    return original_import(name, *args, **kwargs)

builtins.__import__ = secure_import

with open('solution.py', 'r') as file:
    user_code = file.read()
    exec(user_code, {'__builtins__': builtins, '__name__': '__main__'})
PYTHON;

        file_put_contents("$userProblemDir/solution.py", $code);
        file_put_contents("$userProblemDir/secure_runner.py", $securityWrapper);

        foreach ($problem->getTestCases() as $i => $input) {
            $inputData = is_string($input) ? $input : json_encode($input);
            file_put_contents("$userProblemDir/input.txt", $inputData);

            $containerName = 'runner_' . bin2hex(random_bytes(8));
            $containerDir = '/solution';

            try {
                $create = new Process([
                    'docker', 'create',
                    '--network', 'none',
                    '--cpus', '0.5',
                    '--memory', '64m',
                    '--security-opt', 'no-new-privileges',
                    '--cap-drop', 'ALL',
                    '--tmpfs', '/tmp:size=10M,noexec,nosuid,nodev',
                    '--name', $containerName,
                    'python:3.10-slim',
                    'bash', '-c',
                    "cd $containerDir && timeout {$maxRuntime}s python secure_runner.py < input.txt 2>&1"
                ]);
                $create->mustRun();

                $cp = new Process([
                    'docker', 'cp',
                    $userProblemDir,
                    "{$containerName}:$containerDir"
                ]);
                $cp->mustRun();

                $start = new Process([
                    'docker', 'start', '-a', $containerName
                ]);
                $start->setTimeout($maxRuntime + 2);
                $start->run();

                $stdout = trim($start->getOutput());
                $stderr = trim($start->getErrorOutput());

                $rm = new Process(['docker', 'rm', $containerName]);
                $rm->run();

                if ($start->getExitCode() === 124) {
                    throw new ProcessTimedOutException($start, ProcessTimedOutException::TYPE_GENERAL);
                }

                if (!empty($stderr)) {
                    $results[] = [
                        'input'    => $input,
                        'expected' => $problem->getExpectedOutputs()[$i],
                        'output'   => null,
                        'passed'   => false,
                        'error'    => $stderr,
                    ];
                } else {
                    $passed = ($stdout === (string)$problem->getExpectedOutputs()[$i]);
                    $results[] = [
                        'input'    => $input,
                        'expected' => $problem->getExpectedOutputs()[$i],
                        'output'   => $stdout,
                        'passed'   => $passed,
                        'error'    => null,
                    ];
                    if (!$passed) {
                        return $results;
                    }
                }
            } catch (ProcessTimedOutException $e) {
                $results[] = [
                    'input'    => $input,
                    'expected' => $problem->getExpectedOutputs()[$i],
                    'output'   => "Time limit exceeded",
                    'passed'   => false,
                    'error'    => 'Time limit exceeded',
                ];
                (new Process(['docker', 'rm', '-f', $containerName]))->run();
                return $results;
            }
        }

        return $results;
    }
    
    private function validateCode(string $code): bool
    {
        foreach ($this->bannedPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return false;
            }
        }
        
        return true;
    }
}
