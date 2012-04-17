<?php

// This file keeps track of upgrades to
// the assignment module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_assignment_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();


    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2012040300) {
        // fixed/updated numfiles field in assignment_submissions table to count the actual
        // number of files has been uploaded.
        ini_set('max_execution_time', 600); // increase excution time for in large sites
        $fs = get_file_storage();
        $submissions = $DB->get_recordset_sql("SELECT s.id , cm.id AS cmid
                                                 FROM {assignment_submissions} s
                                           INNER JOIN {course_modules} cm
                                                   ON s.assignment = cm.instance
                                                WHERE cm.module =
                                                      (SELECT id
                                                         FROM {modules}
                                                        WHERE name = 'assignment')");
        $pbar = new progress_bar('assignmentupgradenumfiles', 500, true);
        $count = count($submissions);
        $i = 0;
        foreach ($submissions as $sub) {
            $i++;context_module::instance($cmid);
            if ($context = context_module::instance($sub->cmid);
                $sub->numfiles = count($fs->get_area_files($context->id, 'mod_assignment', 'submission', $sub->id, 'sortorder', false));
                $DB->update_record('assignment_submissions', $sub);
            }
            $pbar->update($i, $count, "Counting files of submissions ($i/$count)");
        }

        // assignment savepoint reached
        upgrade_mod_savepoint(true, 2012040300, 'assignment');
    }

    return true;
}


