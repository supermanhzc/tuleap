<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'SVN_PermissionsManager.class.php';
require_once 'SVN_Svnlook.class.php';

class SVN_RepositoryListing {
    /**
     * @var SVN_PermissionsManager
     */
    private $svn_permissions_manager;

    /**
     * @var SVN_Svnlook
     */
    private $svnlook;

    public function __construct(SVN_PermissionsManager $svn_permissions_manager, SVN_Svnlook $svnlook) {
        $this->svn_permissions_manager = $svn_permissions_manager;
        $this->svnlook                 = $svnlook;
    }

    public function getSvnPath(PFUser $user, Project $project, $svn_path) {
        $paths            = array();
        $content          = $this->svnlook->getDirectoryListing($project, $svn_path);
        foreach ($content as $line) {
            if ($this->svn_permissions_manager->userCanRead($user, $project, $line)) {
                $paths[]= $this->extractDirectoryContent($line, $svn_path);
            }
        }
        return array_filter($paths);
    }

    private function extractDirectoryContent($line, $svn_path) {
        $match_path_regex = "%^$svn_path/%";
        if (preg_match($match_path_regex, $line)) {
            return trim(preg_replace($match_path_regex, '', $line), '/');
        }
        return '';
    }
}

?>
