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
 * Print private files tree
 *
 * @package    block_private_files
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_private_files_renderer extends plugin_renderer_base {

    /**
     * Prints private files tree view
     * @return string
     */
    public function private_files_tree() {
        return $this->render(new private_files_tree);
    }

    /**
     * Renders a private files tree.
     *
     * @param private_files_tree $tree
     * @return string The html rendered.
     */
    public function render_private_files_tree(private_files_tree $tree) {
        $data = $tree->export_for_template($this);
        return $this->render_from_template('core/filetree', $data);
    }
}

/**
 * Private files tree renderable.
 *
 * @package    block_private_files
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class private_files_tree implements renderable, templatable {
    public $context;
    public $dir;
    /**
     * Constructor.
     */
    public function __construct() {
        global $USER;
        $this->context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'user', 'private', 0);
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return array data required by template
     */
    public function export_for_template(renderer_base $output) {
        return [
            'foldersexpanded' => false,
            'files' => $this->prepare_dir_for_template($this->dir, $output)
        ];
    }

    /**
     * Generates the tree structure required by core/filetree template for a dir
     *
     * @param array $dir dir tree from $fs->get_area_tree()
     * @param renderer_base $output
     * @return array tree structure required by core/filetree template.
     */
    protected function prepare_dir_for_template($dir, $output) {
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
     * @param renderable $page
     * @return array
     */
    protected function prepare_file_for_template($file, $output) {
        global $CFG;

        $filename = $file->get_filename();
        $path = "/{$this->context->id}/user/private{$file->get_filepath()}/$filename";

        $data = [];
        $data['url'] = file_encode_url("$CFG->wwwroot/pluginfile.php", $path, true);
        $data['icon'] = $output->pix_icon(file_file_icon($file), $filename, 'moodle', array('class' => 'icon'));
        $data['title'] = $filename;
        $data['isdir'] = false;

        return $data;
    }
}
