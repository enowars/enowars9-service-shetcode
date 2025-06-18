<?php

namespace App\Service;

use App\Entity\Problem;
use App\Entity\PrivateProblem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CodeExecutor
{
    private string $dockerHost;
    private string $dockerPort;

    public function __construct()
    {
        $this->dockerHost = "code-executor";
        $this->dockerPort = "2376";
    }

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

        foreach ($problem->getTestCases() as $i => $input) {
            $inputData = is_string($input) ? $input : json_encode($input);
            file_put_contents("$userProblemDir/input.txt", $inputData);

            $containerName = 'runner_' . bin2hex(random_bytes(8));

            try {
                $create = new Process([
                    'docker', '-H', "tcp://{$this->dockerHost}:{$this->dockerPort}",
                    'create',
                    '--network', 'none',
                    '--cpus', '0.5',
                    '--memory', '64m',
                    '--name', $containerName,
                    '--user', '1000:1000',
                    'python:3.10-slim',
                    'bash', '-c',
                    "timeout {$maxRuntime}s python submissions/$userId/{$problemIdentifier}/solution.py < submissions/$userId/{$problemIdentifier}/input.txt 2>&1"
                ]);
                $create->setTimeout(10);
                $create->mustRun();

                $cp = new Process([
                    'docker', '-H', "tcp://{$this->dockerHost}:{$this->dockerPort}",
                    'cp', $submissionsRoot, "{$containerName}:/submissions"
                ]);
                $cp->setTimeout(10);
                $cp->mustRun();

                $start = new Process([
                    'docker', '-H', "tcp://{$this->dockerHost}:{$this->dockerPort}",
                    'start', '-a', $containerName
                ]);
                $start->setTimeout($maxRuntime + 2);
                $start->run();

                $stdout = trim($start->getOutput());
                $stderr = trim($start->getErrorOutput());

                $rm = new Process([
                    'docker', '-H', "tcp://{$this->dockerHost}:{$this->dockerPort}",
                    'rm', $containerName
                ]);
                $rm->setTimeout(10);
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
                (new Process([
                    'docker', '-H', "tcp://{$this->dockerHost}:{$this->dockerPort}",
                    'rm', '-f', $containerName
                ]))->run();
                return $results;
            }
        }

        return $results;
    }
}
