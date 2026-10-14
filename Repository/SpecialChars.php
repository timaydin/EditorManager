<?php

/*!
 * KL/EditorManager/Repository/SpecialChars.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use KL\EditorManager\Entity\SpecialCharacter;
use KL\EditorManager\Entity\SpecialCharacterGroup;
use XF;
use XF\Entity\User;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

/**
 * Class SpecialChars
 * @package KL\EditorManager\Repository
 */
class SpecialChars extends Repository
{
    /**
     * All groups, including inactive ones (admin).
     */
    public function findGroupsForList(): Finder
    {
        return $this->finder('KL\EditorManager:SpecialCharacterGroup')
            ->order(['display_order', 'group_id']);
    }

    /**
     * All characters of the given group(s), including inactive ones (admin).
     *
     * @param int|int[] $groupIds
     */
    public function findCharactersForList($groupIds): Finder
    {
        return $this->finder('KL\EditorManager:SpecialCharacter')
            ->where('group_id', $groupIds)
            ->order(['display_order', 'character_id']);
    }

    /**
     * Active groups whose user criteria match the given user (editor).
     */
    public function getCategoriesForList(?User $user = null): AbstractCollection
    {
        $user = $user ?: XF::visitor();

        return $this->findGroupsForList()
            ->where('active', 1)
            ->fetch()
            ->filter(function (SpecialCharacterGroup $group) use ($user) {
                $userCriteria = $this->app()->criteria('XF:User', $group->user_criteria ?: []);
                $userCriteria->setMatchOnEmpty(true);

                return $userCriteria->isMatched($user);
            });
    }

    /**
     * Active characters of the given groups (editor).
     *
     * @param int[] $groupIds
     */
    public function getCharactersForList(array $groupIds): AbstractCollection
    {
        if (!$groupIds) {
            return $this->em->getEmptyCollection();
        }

        return $this->findCharactersForList($groupIds)
            ->where('active', 1)
            ->fetch();
    }

    /**
     * Characters visible to the current visitor whose title contains the string, shortest titles first.
     *
     * @param string $string
     * @param int $limit
     * @return SpecialCharacter[]
     */
    public function getMatchingCharactersByString(string $string, int $limit = 20): array
    {
        $characters = $this->getCharactersForList($this->getCategoriesForList()->keys());

        $results = [];
        foreach ($characters as $id => $character) {
            $title = (string)$character->title;
            if (stripos($title, $string) !== false || $character->code === $string) {
                $results[$id] = $character;
            }
        }

        uasort($results, function (SpecialCharacter $a, SpecialCharacter $b) {
            return strlen((string)$a->title) <=> strlen((string)$b->title);
        });

        return array_slice($results, 0, $limit, true);
    }
}
