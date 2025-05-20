<?php

namespace App\Service;

use App\Entity\Problem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CodeExecutor
{
    public function executeUserCode(string $code, Problem $problem, mixed $userId): array
    {
        $maxRuntime = min($problem->getMaxRuntime(), 1);
        $results = [];
        $submissionsRoot = getcwd() . '/submissions';
        $userProblemDir = "$submissionsRoot/$userId/{$problem->getId()}";

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
                    'docker', 'create',
                    '--network', 'none',
                    '--cpus', '0.5',
                    '--memory', '64m',
                    '--name', $containerName,
                    'python:3.10-slim',
                    'bash', '-c',
                    "timeout {$maxRuntime}s python submissions/$userId/{$problem->getId()}/solution.py < submissions/$userId/{$problem->getId()}/input.txt 2>&1"
                ]);
                $create->mustRun();

                $cp = new Process([
                    'docker', 'cp',
                    $submissionsRoot,
                    "{$containerName}:/submissions"
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
}
