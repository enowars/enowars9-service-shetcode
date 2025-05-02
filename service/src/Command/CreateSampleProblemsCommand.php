<?php

namespace App\Command;

use App\Entity\Problem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-sample-problems',
    description: 'Creates sample coding problems',
)]
class CreateSampleProblemsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sampleProblems = [
            [
                'title' => 'Two Sum',
                'description' => 'Given an array of integers nums and an integer target, return indices of the two numbers such that they add up to target. You may assume that each input would have exactly one solution, and you may not use the same element twice.',
                'difficulty' => 'Easy',
                'testCases' => [
                    '[2,7,11,15], 9',
                    '[3,2,4], 6',
                    '[3,3], 6',
                ],
                'expectedOutputs' => [
                    '[0,1]',
                    '[1,2]',
                    '[0,1]',
                ],
                'isPublished' => true,
            ],
            [
                'title' => 'Valid Parentheses',
                'description' => 'Given a string s containing just the characters \'(\', \')\', \'{\', \'}\', \'[\' and \']\', determine if the input string is valid. An input string is valid if: Open brackets must be closed by the same type of brackets, and Open brackets must be closed in the correct order.',
                'difficulty' => 'Easy',
                'testCases' => [
                    '()',
                    '()[]{}',
                    '(]',
                ],
                'expectedOutputs' => [
                    'true',
                    'true',
                    'false',
                ],
                'isPublished' => true,
            ],
            [
                'title' => 'Merge Two Sorted Lists',
                'description' => 'You are given the heads of two sorted linked lists list1 and list2. Merge the two lists in a one sorted list. The list should be made by splicing together the nodes of the first two lists. Return the head of the merged linked list.',
                'difficulty' => 'Easy',
                'testCases' => [
                    '[1,2,4], [1,3,4]',
                    '[], []',
                    '[], [0]',
                ],
                'expectedOutputs' => [
                    '[1,1,2,3,4,4]',
                    '[]',
                    '[0]',
                ],
                'isPublished' => true,
            ],
            [
                'title' => 'Add Two Numbers',
                'description' => 'You are given two non-empty linked lists representing two non-negative integers. The digits are stored in reverse order, and each of their nodes contains a single digit. Add the two numbers and return the sum as a linked list.',
                'difficulty' => 'Medium',
                'testCases' => [
                    '[2,4,3], [5,6,4]',
                    '[0], [0]',
                    '[9,9,9,9], [9,9,9,9,9,9,9]',
                ],
                'expectedOutputs' => [
                    '[7,0,8]',
                    '[0]',
                    '[8,9,9,9,0,0,0,1]',
                ],
                'isPublished' => true,
            ],
            [
                'title' => 'Longest Substring Without Repeating Characters',
                'description' => 'Given a string s, find the length of the longest substring without repeating characters.',
                'difficulty' => 'Medium',
                'testCases' => [
                    'abcabcbb',
                    'bbbbb',
                    'pwwkew',
                ],
                'expectedOutputs' => [
                    '3',
                    '1',
                    '3',
                ],
                'isPublished' => false, // Draft problem
            ],
        ];

        foreach ($sampleProblems as $problemData) {
            $problem = new Problem();
            $problem->setTitle($problemData['title']);
            $problem->setDescription($problemData['description']);
            $problem->setDifficulty($problemData['difficulty']);
            $problem->setTestCases($problemData['testCases']);
            $problem->setExpectedOutputs($problemData['expectedOutputs']);
            $problem->setIsPublished($problemData['isPublished']);

            $this->entityManager->persist($problem);
        }

        $this->entityManager->flush();

        $io->success('Sample problems have been created!');

        return Command::SUCCESS;
    }
} 