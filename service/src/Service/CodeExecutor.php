<?php

namespace App\Service;

use App\Entity\Problem;
use App\Entity\PrivateProblem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CodeExecutor
{

    public function executeUserCode(string $code, Problem $problem, mixed $userId): array
    {
        return $this->executeCode($code, $problem, $userId, 'public');
    }
    
    public function executeUserCodeForPrivateProblem(string $code, PrivateProblem $problem, mixed $userId): array
    {
        return $this->executeCode($code, $problem, $userId, 'private');
    }
    
    private function executeCode(string $code, Problem|PrivateProblem $problem, mixed $userId, string $type): array
    {
        $maxRuntime = min($problem->getMaxRuntime(), 1);
        $results = [];
        $submissionsRoot = getcwd() . '/submissions';
        
        $problemIdentifier = $type === 'private' ? "private_{$problem->getId()}" : $problem->getId();
        $userProblemDir = "$submissionsRoot/$userId/{$problemIdentifier}";

        if (!is_dir($userProblemDir)) {
            mkdir($userProblemDir, 0777, true);
        }

        file_put_contents("$userProblemDir/solution.py", $code);
        file_put_contents("$userProblemDir/runner.py", file_get_contents(__DIR__ . '/../../docker/runner.py'));

        foreach ($problem->getTestCases() as $i => $input) {
            $inputData = is_string($input) ? $input : json_encode($input);

            try {
                $cmd = [
                    'nsjail',
                    '--user',         '99999',
                    '--group',        '99999',
                    '--disable_proc',
                    '--bindmount_ro', '/var/www/html/public/submissions:/var/www/html/public/submissions',
                    '--bindmount',    "$userProblemDir:/sandbox:rw",
                    '--chroot',       '/',
                    '--cwd',          '/sandbox',
                    '--',             '/usr/bin/python3', 'runner.py',
                  ];
                
                $proc = new Process(
                    $cmd,
                    null,
                    null,
                    $inputData,
                    $maxRuntime
                );
                $proc->setTimeout($maxRuntime + 2);
                $proc->run();
                
                $stdout = trim($proc->getOutput());
                $stderr = trim($proc->getErrorOutput());
                $clean = preg_replace(
                    '/^\[.*\].*\R?/m',
                    '',
                    $stderr
                );

                if ($proc->getExitCode() === 124) {
                    throw new ProcessTimedOutException($proc, ProcessTimedOutException::TYPE_GENERAL);
                }

                if (!empty($clean) && $clean !== '') {
                    $results[] = [
                        'input'    => $input,
                        'expected' => $problem->getExpectedOutputs()[$i],
                        'output'   => $clean,
                        'passed'   => false,
                        'error'    => $clean,
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
                return $results;
            }
        }

        return $results;
    }
}
