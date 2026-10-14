<?php

/*!
 * KL/EditorManager/Setup.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 * Reduced to the special characters feature for XenForo 2.3.
 */

namespace KL\EditorManager;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

/**
 * Class Setup
 * @package KL\EditorManager
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * Version 3 only contains the special characters feature. Upgrading a full 2.x install in place would
     * leave its other tables, columns and data behind, so require 2.x to be uninstalled first.
     *
     * @param array $errors
     * @param array $warnings
     */
    public function checkRequirements(&$errors = [], &$warnings = []): void
    {
        $installed = $this->addOn->getInstalledAddOn();
        if ($installed && $installed->version_id < 3000000) {
            $errors[] = 'Editor Manager ' . $installed->version_string
                . ' is installed. Uninstall it before installing this version, which only contains the special characters feature.';
        }
    }

    public function installStep1(): void
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars_groups', function (Create $table) {
            $table->checkExists(true);
            $table->addColumn('group_id', 'int')->autoIncrement();
            $table->addColumn('user_criteria', 'mediumblob');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
        });
    }

    public function installStep2(): void
    {
        $this->schemaManager()->createTable('xf_kl_em_special_chars', function (Create $table) {
            $table->checkExists(true);
            $table->addColumn('character_id', 'int')->autoIncrement();
            $table->addColumn('code', 'varchar', 25);
            $table->addColumn('group_id', 'int');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'bool')->setDefault(true);
            $table->addKey('group_id');
        });
    }

    public function uninstallStep1(): void
    {
        $this->schemaManager()->dropTable('xf_kl_em_special_chars_groups');
        $this->schemaManager()->dropTable('xf_kl_em_special_chars');
    }

    public function uninstallStep2(): void
    {
        // group and character titles are master phrases that are not owned by the add-on
        $this->db()->delete('xf_phrase', 'title LIKE ? OR title LIKE ?', [
            'kl\_em\_sc\_group\_id.%',
            'kl\_em\_sc\_char\_id.%',
        ]);
    }
}
