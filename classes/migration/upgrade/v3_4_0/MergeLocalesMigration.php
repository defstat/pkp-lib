<?php

/**
 * @file classes/migration/upgrade/v3_4_0/MergeLocalesMigration.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7191_SubmissionChecklistMigration
 * @brief Migrate the submissionChecklist setting from an array to a HTML string
 */

namespace PKP\migration\upgrade\v3_4_0;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use PKP\install\DowngradeNotSupportedException;

// abstract class MergeLocalesMigration extends \PKP\migration\Migration
class MergeLocalesMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = null;

        $databaseName = DB::getDatabaseName();

        // All _settings tables.
        switch (DB::getDriverName()) {
            case 'pgsql':
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE tablename LIKE '%_settings' AND schemaname='public'");
                break;
            case 'mysql':
                $tables = DB::select('SHOW TABLES LIKE "%_settings"');
                break;
        }

        foreach($tables as $table) {
            $tableName = $table->{"Tables_in_$databaseName (%_settings)"};
            if (Schema::hasColumn($tableName, 'locale')) {
                DB::table($tableName)->update(['locale' => DB::raw("SUBSTR(locale, 1, 2)")]);
            }
        }

        // Tables
        // site
        $site = DB::table('site')
            ->select(['supported_locales', 'installed_locales', 'primary_locale'])
            ->first();

        $this->updateArrayLocaleNoId($site->supported_locales, 'site', 'supported_locales');
        $this->updateArrayLocaleNoId($site->installed_locales, 'site', 'installed_locales');
        $this->updateSingleValueLocaleNoId($site->primary_locale, 'site', 'primary_locale');
        
        // users
        $users = DB::table('users')
            ->get();

        foreach ($users as $user) {
            $this->updateArrayLocale($user->locales, 'users', 'locales', 'user_id', $user->user_id);
        }

        // submissions
        $submissions = DB::table('submissions')
            ->get();

        foreach ($submissions as $submission) {
            $this->updateSingleValueLocale($submission->locale, 'submissions', 'locale', 'submission_id', $submission->submission_id);
        }
        
        // publication_galleys
        $publicationGalleys = DB::table('publication_galleys')
            ->get();

        foreach ($publicationGalleys as $publicationGalley) {
            $this->updateSingleValueLocale($publicationGalley->locale, 'publication_galleys', 'locale', 'galley_id', $publicationGalley->galley_id);
        }

        // email_templates_default_data
        $emailTemplatesDefaultData = DB::table('email_templates_default_data')
            ->get();

        foreach ($emailTemplatesDefaultData as $emailTemplatesDefaultDataCurrent) {
            $this->updateSingleValueLocaleEmailData($emailTemplatesDefaultDataCurrent->locale, 'email_templates_default_data', 'locale', 'email_key', $emailTemplatesDefaultDataCurrent->email_key);
        }

        // Those should not be here - I added them here just for demonstration purposes
        // journals
        $journals = DB::table('journals')
            ->get();

        foreach ($journals as $journal) {
            $this->updateSingleValueLocale($journal->primary_locale, 'journals', 'primary_locale', 'journal_id', $journal->journal_id);
        }

        // issue_galleys
        $issueGalleys = DB::table('issue_galleys')
            ->get();

        foreach ($issueGalleys as $issueGalley) {
            $this->updateSingleValueLocale($issueGalley->locale, 'issue_galleys', 'locale', 'galley_id', $issueGalley->galley_id);
        }

        // journal_settings
        $journalSettingsFormLocales = DB::table('journal_settings')
            ->where('setting_name', '=', 'supportedFormLocales')
            ->get();

        foreach ($journalSettingsFormLocales as $journalSettingsFormLocale) {
            $this->updateArrayLocaleSetting($journalSettingsFormLocale->setting_value, 'journal_settings', 'primary_locale', 'journal_id', $journalSettingsFormLocale->journal_id);
        }
    }

    function updateArrayLocaleNoId(string $dbLocales, string $table, string $column) 
    {
        $siteSupportedLocales = json_decode($dbLocales);

        if ($siteSupportedLocales !== false) {
            $newLocales = [];
            foreach ($siteSupportedLocales as $siteSupportedLocale) {
                $newLocales[] = substr($siteSupportedLocale, 0, 2);
            }

            DB::table($table)
                ->update([
                    $column => $newLocales
                ]);
        }
    }

    function updateArrayLocale(string $dbLocales, string $table, string $column, string $tableKeyColumn, int $id) 
    {
        $siteSupportedLocales = json_decode($dbLocales);

        if ($siteSupportedLocales !== false) {
            $newLocales = [];
            foreach ($siteSupportedLocales as $siteSupportedLocale) {
                $newLocales[] = substr($siteSupportedLocale, 0, 2);
            }

            DB::table($table)
                ->where($tableKeyColumn, '=', $id)
                ->update([
                    $column => $newLocales
                ]);
        }
    }

    function updateArrayLocaleSetting(string $dbLocales, string $table, string $settingValue, string $tableKeyColumn, int $id) 
    {
        $siteSupportedLocales = json_decode($dbLocales);

        if ($siteSupportedLocales !== false) {
            $newLocales = [];
            foreach ($siteSupportedLocales as $siteSupportedLocale) {
                $newLocales[] = substr($siteSupportedLocale, 0, 2);
            }

            DB::table($table)
                ->where($tableKeyColumn, '=', $id)
                ->where('setting_name', '=', $settingValue)
                ->update([
                    'setting_value' => $newLocales
                ]);
        }
    }

    function updateSingleValueLocale(string $localevalue, string $table, string $column, string $tableKeyColumn, int $id) 
    {
        DB::table($table)
            ->where($tableKeyColumn, '=', $id)
            ->update([
                $column => substr($localevalue, 0, 2)
            ]);
    }

    function updateSingleValueLocaleNoId(string $localevalue, string $table, string $column) 
    {
        DB::table($table)
            ->update([
                $column => substr($localevalue, 0, 2)
            ]);
    }

    function updateSingleValueLocaleEmailData(string $localevalue, string $table, string $column, string $tableKeyColumn, string $id) 
    {
        DB::table($table)
            ->where($tableKeyColumn, '=', $id)
            ->where($column, '=', $localevalue)
            ->update([
                $column => substr($localevalue, 0, 2)
            ]);
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
