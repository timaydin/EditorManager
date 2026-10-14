<?php /** @noinspection PhpUnusedParameterInspection */

/*!
 * KL/EditorManager/Listener/EditorButtonData.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use XF;
use XF\Data\Editor;

/**
 * Class EditorButtonData
 * @package KL\EditorManager\Listener
 */
class EditorButtonData
{
    /**
     * @param array $buttons
     * @param Editor $editorData
     */
    public static function extend(array &$buttons, Editor $editorData): void
    {
        $buttons['specialCharacters'] = [
            'fa' => 'fa-omega',
            'title' => XF::phrase('kl_em_special_characters'),
            // opens a menu anchored to the toolbar button, so it can't live inside another dropdown
            'type' => 'dropdown',
        ];
    }
}
