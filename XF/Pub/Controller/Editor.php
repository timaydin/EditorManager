<?php

/*!
 * KL/EditorManager/XF/Pub/Controller/Editor.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\Controller;

use KL\EditorManager\Repository\SpecialChars;
use XF\Mvc\Reply\AbstractReply;

/**
 * Class Editor
 * @package KL\EditorManager\XF\Pub\Controller
 */
class Editor extends XFCP_Editor
{
    public const RECENT_COOKIE = 'klem_specialcharacter_usage';

    protected function getKLSpecialCharacterRepo(): SpecialChars
    {
        return $this->repository('KL\EditorManager:SpecialChars');
    }

    public function actionKlEmSpecialChars(): AbstractReply
    {
        $repo = $this->getKLSpecialCharacterRepo();
        $categories = $repo->getCategoriesForList();
        $characters = $repo->getCharactersForList($categories->keys());

        $recent = [];
        $recentlyUsed = $this->request->getCookie(self::RECENT_COOKIE, '');
        if ($recentlyUsed) {
            foreach (array_reverse(explode(',', $recentlyUsed)) as $id) {
                if (isset($characters[$id])) {
                    $recent[$id] = $characters[$id];
                }
            }
        }

        $viewParams = [
            'recent' => $recent,
            'groupedCharacters' => $characters->groupBy('group_id'),
            'categories' => $categories,
        ];
        return $this->view('KL\EditorManager:Editor\SpecialCharacters', 'kl_em_editor_special_characters', $viewParams);
    }

    public function actionKlEmSpecialCharsSearch(): AbstractReply
    {
        $q = ltrim($this->filter('q', 'str', ['no-trim']));

        if ($q !== '' && utf8_strlen($q) >= 2) {
            $results = $this->getKLSpecialCharacterRepo()->getMatchingCharactersByString($q, 20);
        } else {
            $results = [];
            $q = '';
        }

        $viewParams = [
            'q' => $q,
            'results' => $results,
        ];
        return $this->view('KL\EditorManager:Editor\SpecialCharacters\Search',
            'kl_em_editor_special_characters_search_results', $viewParams);
    }
}
