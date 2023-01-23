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
                DB::table($tableName)->where('locale', 'like', 'es_%')->update(['locale' => 'es']);
            }
            
        }

        // Tables
        // site
        $site = DB::table('site')
            ->select(['supported_locales', 'installed_locales', 'primary_locale'])
            ->first();

        $this->updateArrayLocaleNoId($site->supported_locales, ['es_MX', 'es_ES'], 'es', 'site', 'supported_locales');
        $this->updateArrayLocaleNoId($site->installed_locales, ['es_MX', 'es_ES'], 'es', 'site', 'installed_locales');
        $this->updateSingleValueLocaleNoId($site->primary_locale, ['es_MX', 'es_ES'], 'es', 'site', 'primary_locale');
        
        // users
        $users = DB::table('users')
            ->select('locales')
            ->get();

        foreach ($users as $user) {
            $this->updateArrayLocale($user->locales, ['es_MX', 'es_ES'], 'es', 'users', 'locales', 'user_id', $user->user_id);
        }

        // submissions
        $submissions = DB::table('submissions')
            ->select('locale')
            ->get();

        foreach ($submissions as $submission) {
            $this->updateSingleValueLocale($submission->locale, ['es_MX', 'es_ES'], 'es', 'submissions', 'locale', 'submission_id', $submission->submission_id);
        }
        
        // publication_galleys
        $publicationGalleys = DB::table('publication_galleys')
            ->select('locale')
            ->get();

        foreach ($publicationGalleys as $publicationGalley) {
            $this->updateSingleValueLocale($publicationGalley->locale, ['es_MX', 'es_ES'], 'es', 'publication_galleys', 'locale', 'galley_id', $publicationGalley->galley_id);
        }

        // email_templates_default_data
        $emailTemplatesDefaultData = DB::table('email_templates_default_data')
            ->select('locale')
            ->get();

        foreach ($emailTemplatesDefaultData as $emailTemplatesDefaultDataCurrent) {
            $this->updateSingleValueLocaleEmailData($emailTemplatesDefaultDataCurrent->locale, ['es_MX', 'es_ES'], 'es', 'email_templates_default_data', 'locale', 'email_key', $emailTemplatesDefaultDataCurrent->email_key);
        }

        // Those should not be here - I added them here just for demonstration purposes
        // journals
        $journals = DB::table('journals')
            ->select('primary_locale')
            ->get();

        foreach ($journals as $journal) {
            $this->updateSingleValueLocale($journal->primary_locale, ['es_MX', 'es_ES'], 'es', 'journals', 'primary_locale', 'journal_id', $journal->journal_id);
        }

        // issue_galleys
        $issueGalleys = DB::table('issue_galleys')
            ->select('locale')
            ->get();

        foreach ($issueGalleys as $issueGalley) {
            $this->updateSingleValueLocale($issueGalley->locale, ['es_MX', 'es_ES'], 'es', 'issue_galleys', 'locale', 'galley_id', $issueGalley->galley_id);
        }

        // journal_settings
        $journalSettingsFormLocales = DB::table('journal_settings')
            ->where('setting_name', '=', 'supportedFormLocales')
            ->get();

        foreach ($journalSettingsFormLocales as $journalSettingsFormLocale) {
            $this->updateArrayLocaleSetting($journalSettingsFormLocale->setting_value, ['es_MX', 'es_ES'], 'es', 'journal_settings', 'primary_locale', 'journal_id', $journalSettingsFormLocale->journal_id);
        }
    }

    function updateArrayLocaleNoId(string $dbLocales, array $locales, string $targetLocale, string $table, string $column) 
    {
        $siteSupportedLocales = json_decode($dbLocales);

        if ($siteSupportedLocales !== false) {
            $newLocales = [];
            foreach ($siteSupportedLocales as $siteSupportedLocale) {
                if (in_array($siteSupportedLocale, $locales)) {
                    $newLocales[] = $targetLocale;
                } else {
                    $newLocales[] = $siteSupportedLocale;
                }
            }

            DB::table($table)
                ->update([
                    $column => $newLocales
                ]);
        }
    }

    function updateArrayLocale(string $dbLocales, array $locales, string $targetLocale, string $table, string $column, string $tableKeyColumn, int $id) 
    {
        $siteSupportedLocales = json_decode($dbLocales);

        if ($siteSupportedLocales !== false) {
            $newLocales = [];
            foreach ($siteSupportedLocales as $siteSupportedLocale) {
                if (in_array($siteSupportedLocale, $locales)) {
                    $newLocales[] = $targetLocale;
                } else {
                    $newLocales[] = $siteSupportedLocale;
                }
            }

            DB::table($table)
                ->where($tableKeyColumn, '=', $id)
                ->update([
                    $column => $newLocales
                ]);
        }
    }

    function updateArrayLocaleSetting(string $dbLocales, array $locales, string $targetLocale, string $table, string $settingValue, string $tableKeyColumn, int $id) 
    {
        $siteSupportedLocales = json_decode($dbLocales);

        if ($siteSupportedLocales !== false) {
            $newLocales = [];
            foreach ($siteSupportedLocales as $siteSupportedLocale) {
                if (in_array($siteSupportedLocale, $locales)) {
                    $newLocales[] = $targetLocale;
                } else {
                    $newLocales[] = $siteSupportedLocale;
                }
            }

            DB::table($table)
                ->where($tableKeyColumn, '=', $id)
                ->where('setting_name', '=', $settingValue)
                ->update([
                    'setting_value' => $newLocales
                ]);
        }
    }

    function updateSingleValueLocale(string $localevalue, array $locales, string $targetLocale, string $table, string $column, string $tableKeyColumn, int $id) 
    {
        if (in_array($localevalue, $locales)) {
            DB::table($table)
                ->where($tableKeyColumn, '=', $id)
                ->update([
                    $column => $targetLocale
                ]);
        }
    }

    function updateSingleValueLocaleNoId(string $localevalue, array $locales, string $targetLocale, string $table, string $column) 
    {
        if (in_array($localevalue, $locales)) {
            DB::table($table)
                ->update([
                    $column => $targetLocale
                ]);
        }
    }

    function updateSingleValueLocaleEmailData(string $localevalue, array $locales, string $targetLocale, string $table, string $column, string $tableKeyColumn, int $id) 
    {
        if (in_array($localevalue, $locales)) {
            DB::table($table)
                ->where($tableKeyColumn, '=', $id)
                ->where($column, '=', $localevalue)
                ->update([
                    $column => $targetLocale
                ]);
        }
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
