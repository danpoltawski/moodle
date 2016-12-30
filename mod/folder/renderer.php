<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Folder module renderer
 *
 * @package   mod_folder
 * @copyright 2009 Petr Skoda  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_folder_renderer extends plugin_renderer_base {

    /**
     * Returns html to display the content of mod_folder
     * (Description, folder files and optionally Edit button)
     *
     * @param stdClass $folder record from 'folder' table (please note
     *     it may not contain fields 'revision' and 'timemodified')
     * @return string
     */
    public function display_folder(stdClass $folder) {
        $output = '';
        $folderinstances = get_fast_modinfo($folder->course)->get_instances_of('folder');
        if (!isset($folderinstances[$folder->id]) ||
                !($cm = $folderinstances[$folder->id]) ||
                !($context = context_module::instance($cm->id))) {
            // Some error in parameters.
            // Don't throw any errors in renderer, just return empty string.
            // Capability to view module must be checked before calling renderer.
            return $output;
        }

        if (trim($folder->intro)) {
            if ($folder->display != FOLDER_DISPLAY_INLINE) {
                $output .= $this->output->box(format_module_intro('folder', $folder, $cm->id),
                        'generalbox', 'intro');
            } else if ($cm->showdescription) {
                // for "display inline" do not filter, filters run at display time.
                $output .= format_module_intro('folder', $folder, $cm->id, false);
            }
        }

        $foldertree = new folder_tree($folder, $cm);
        $output .= $this->output->box($this->render($foldertree),
                'generalbox foldertree');

        // Do not append the edit button on the course page.
        if ($folder->display != FOLDER_DISPLAY_INLINE) {
            $containercontents = '';
            $downloadable = folder_archive_available($folder, $cm);

            if ($downloadable) {
                $downloadbutton = $this->output->single_button(
                    new moodle_url('/mod/folder/download_folder.php', array('id' => $cm->id)),
                    get_string('downloadfolder', 'folder')
                );

                $output .= $downloadbutton;
            }

            if (has_capability('mod/folder:managefiles', $context)) {
                $editbutton = $this->output->single_button(
                    new moodle_url('/mod/folder/edit.php', array('id' => $cm->id)),
                    get_string('edit')
                );

                $output .= $editbutton;
            }
        }
        return $output;
    }

    public function render_folder_tree(folder_tree $tree) {
        $data = $tree->export_for_template($this);
        return $this->render_from_template('core/filetree', $data);
    }
}

/**
 * Folder tree rendererable
 *
 * @package   mod_folder
 * @copyright 2009 Petr Skoda  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class folder_tree implements renderable, templatable {
    public $context;
    public $folder;
    public $cm;
    public $dir;

    public function __construct($folder, $cm) {
        $this->folder = $folder;
        $this->cm     = $cm;

        $this->context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'mod_folder', 'content', 0);
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return array data required by template
     */
    public function export_for_template(renderer_base $output) {
        if ($this->folder->display == FOLDER_DISPLAY_INLINE) {
            // If displayed on course page, embed in a folder.
            $files = [
                'title' => $this->cm->get_formatted_name(),
                'isdir' => true,
                'files' => $this->prepare_dir_for_template($this->dir, $output)
            ];
        } else {
            $files = $this->prepare_dir_for_template($this->dir, $output);
        }

        return [
            'foldersexpanded' => !empty($this->folder->showexpanded),
            'files' => $files
        ];
    }

    /**
     * Generates the tree structure required by core/filetree template for a dir
     *
     * @param array $dir dir tree from $fs->get_area_tree()
     * @param renderer_base $output
     * @return array tree structure required by core/filetree template.
     */
    protected function prepare_dir_for_template($dir, renderer_base $output) {
        $files = [];
        foreach ($dir['subdirs'] as $subdir) {
            $dirfiles = $this->prepare_dir_for_template($subdir, $output);
            $files[] = ['title' => $subdir['dirname'], 'isdir' => true, 'files' => $dirfiles];
        }

        foreach ($dir['files'] as $file) {
            $files[] = $this->prepare_file_for_template($file, $output);
        }
        return $files;
    }

    /**
     * Generates the structure required by core/filetree template for a file
     *
     * @param array $file from $fs->get_area_tree()
     * @param renderer_base $output
     * @return array
     */
    protected function prepare_file_for_template($file, renderer_base $output) {
        $filename = $file->get_filename();
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename, false);
        if (file_extension_in_typegroup($filename, 'web_image')) {
            $image = $url->out(false, array('preview' => 'tinyicon', 'oid' => $file->get_timemodified()));
            $image = html_writer::empty_tag('img', array('src' => $image));
        } else {
            $image = $this->output->pix_icon(file_file_icon($file, 24), $filename, 'moodle');
        }

        $data = [];
        $data['url'] = $url->out(false, array('forcedownload' => 1));
        $data['icon'] = $image;
        $data['title'] = $filename;
        $data['isdir'] = false;

        return $data;
    }
}
