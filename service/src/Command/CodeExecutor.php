<?php

namespace App\Command;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CodeExecutor
{
    public function executeUserCode(string $code, array $testCases, array $expectedOutputs, mixed $userId, int $problemId): array
    {
        $results = [];
        // Собираем корень папки submissions
        $submissionsRoot = getcwd() . '/submissions';
        $userProblemDir = "$submissionsRoot/$userId/$problemId";
        if (!is_dir($userProblemDir)) {
            mkdir($userProblemDir, 0777, true);
        }

        // Сохраняем решение
        file_put_contents("$userProblemDir/solution.py", $code);

        foreach ($testCases as $i => $input) {
            $inputData = is_string($input) ? $input : json_encode($input);
            file_put_contents("$userProblemDir/input.txt", $inputData);

            // Уникальное имя контейнера
            $containerName = 'runner_' . bin2hex(random_bytes(8));

            try {
                $create = new Process([
                    'docker', 'create',
                    '--network', 'none',
                    '--cpus', '0.5',
                    '--memory', '256m',
                    '--name', $containerName,
                    'python:3.10-slim',
                    'bash', '-c',
                    // Команда внутри контейнера будет запущена при старте
                    "timeout 2s python submissions/$userId/$problemId/solution.py < submissions/$userId/$problemId/input.txt"
                ]);
                $create->mustRun();


                // 2) копируем всю папку submissions внутрь контейнера
                $cp = new Process([
                    'docker', 'cp',
                    $submissionsRoot,
                    "{$containerName}:/submissions"
                ]);
                $cp->mustRun();

                // 3) запускаем контейнер и ждём завершения
                $start = new Process([
                    'docker', 'start', '-a', $containerName
                ]);
                $start->setTimeout(4);
                $start->run();

                // 4) получаем вывод
                $stdout = trim($start->getOutput());
                $stderr = trim($start->getErrorOutput());

                // 5) удаляем контейнер
                $rm = new Process(['docker', 'rm', $containerName]);
                $rm->run();

                // Анализ результата
                if ($start->getExitCode() === 124) {
                    // 124 — таймаут от timeout(1)
                    throw new ProcessTimedOutException($start, ProcessTimedOutException::TYPE_GENERAL);
                }

                if (!empty($stderr)) {
                    // Ошибка выполнения
                    $results[] = [
                        'input'    => $input,
                        'expected' => $expectedOutputs[$i],
                        'output'   => null,
                        'passed'   => false,
                        'error'    => $stderr,
                    ];
                } else {
                    $passed = ((string)$stdout === (string)$expectedOutputs[$i]);
                    $results[] = [
                        'input'    => $input,
                        'expected' => $expectedOutputs[$i],
                        'output'   => $stdout,
                        'passed'   => $passed,
                        'error'    => null,
                    ];
                }
            } catch (ProcessTimedOutException $e) {
                // Время вышло
                $results[] = [
                    'input'    => $input,
                    'expected' => $expectedOutputs[$i],
                    'output'   => null,
                    'passed'   => false,
                    'error'    => 'Time limit exceeded',
                ];
                // Попробуем удалить контейнер на всякий случай
                (new Process(['docker', 'rm', '-f', $containerName]))->run();
            } catch (ProcessFailedException $e) {
                // Любая другая ошибка Docker (create/cp/start)
                $results[] = [
                    'input'    => $input,
                    'expected' => $expectedOutputs[$i],
                    'output'   => null,
                    'passed'   => false,
                    'error'    => 'Docker error: ' . $e->getMessage(),
                ];
                (new Process(['docker', 'rm', '-f', $containerName]))->run();
            }
        }

        return $results;
    }
}
