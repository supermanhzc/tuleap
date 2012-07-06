<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once CARDWALL_BASE_DIR .'/OnTop/Config/MappingFieldValueCollectionFactory.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerFieldMappingsFactory.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerFieldMappingFactory.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerFieldMapping.class.php';

/**
 * Display the admin of the Cardwall
 */
class Cardwall_AdminView extends Abstract_View {

    public function displayAdminOnTop(Tracker $tracker,
                                       Tracker_IDisplayTrackerLayout $layout,
                                       TrackerFactory $tracker_factory,
                                       Tracker_FormElementFactory $element_factory,
                                       CSRFSynchronizerToken $token,
                                       Cardwall_OnTop_Dao $ontop_dao,
                                       Cardwall_OnTop_ColumnDao $column_dao,
                                       Cardwall_OnTop_ColumnMappingFieldDao $mappings_dao,
                                       Cardwall_OnTop_ColumnMappingFieldValueDao $mapping_values_dao) {

        $tracker_id = $tracker->getId();
        $checked    = $ontop_dao->isEnabled($tracker_id) ? 'checked="checked"' : '';
        $token_html = $token->fetchHTMLInput();
        
        
        $columns_raws = $column_dao->searchColumnsByTrackerId($tracker->getId());
        $columns = array();
        foreach ($columns_raws as $raw) {
            $columns[] = new Cardwall_OnTop_Config_Column($raw['id'], $raw['label']);
        }

        $column_definition_view = new Cardwall_AdminColumnDefinitionView($columns);
        $formview   = new Cardwall_AdminFormView($column_definition_view);

        $mapping_values_factory = new Cardwall_OnTop_Config_MappingFieldValueCollectionFactory($mapping_values_dao, $element_factory);
        $mapping_values         = $mapping_values_factory->getCollection($tracker);

        $mappings_factory = new Cardwall_OnTop_Config_TrackerFieldMappingsFactory($tracker_factory, $mappings_dao, new Cardwall_OnTop_Config_TrackerFieldMappingFactory($element_factory));

        $tracker ->displayAdminItemHeader($layout, 'plugin_cardwall');
        $formview->displayAdminForm($token_html, $checked, $tracker, $mapping_values, $mappings_factory);
        $tracker ->displayFooter($layout);
    }


}

abstract class Abstract_View {

    /**
     * @var Codendi_HTMLPurifier
     */
    private $hp;

    public function __construct() {
        $this->hp = Codendi_HTMLPurifier::instance();
    }

    protected function purify($value) {
        return $this->hp->purify($value);
    }

    protected function translate($page, $category, $args = "") {
        return $GLOBALS['Language']->getText($page, $category, $args);
    }


}

class Cardwall_AdminFormView extends Abstract_View {

    /** @var Cardwall_AdminColumnDefinitionView */
    private $subview;
    
    public function __construct(Cardwall_AdminColumnDefinitionView $column_definition_view) {
        parent::__construct();
        $this->subview = $column_definition_view;
    }
    
    private function urlForAdminUpdate($tracker_id) {
        return TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&amp;func=admin-cardwall-update';
    }

    public function displayAdminForm($token_html, $checked, $tracker, Cardwall_OnTop_Config_MappingFieldValueCollection $mapping_values, $mappings_factory) {
        echo $this->generateAdminForm($token_html, $checked, $tracker, $mapping_values, $mappings_factory);
    }

    private function generateAdminForm($token_html, $checked, $tracker, Cardwall_OnTop_Config_MappingFieldValueCollection $mapping_values, $mappings_factory) {
        $update_url = $this->urlForAdminUpdate($tracker->getId());

        $html  = '';
        $html .= '<form action="'.$update_url .'" METHOD="POST">';
        $html .= $token_html;
        $html .= '<p>';
        $html .= '<input type="hidden" name="cardwall_on_top" value="0" />';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="cardwall_on_top" value="1" id="cardwall_on_top" '. $checked .'/> ';
        $html .= $this->translate('plugin_cardwall', 'on_top_label');
        $html .= '</label>';
        $html .= '</p>';
        if ($checked) {
            $html .= '<blockquote>';
            $html .= $this->subview->fetchColumnDefinition($tracker, $mapping_values, $mappings_factory);
            $html .= '</blockquote>';
        }
        $html .= '<input type="submit" value="'. $this->translate('global', 'btn_submit') .'" />';
        $html .= '</form>';
        return $html;
    }

}
class Cardwall_OnTop_Config_Trackers {

    private $mapped_trackers;
    private $non_mapped_trackers;

    function __construct(array $project_trackers, Tracker $tracker, Cardwall_OnTop_Config_MappingFields $mapping_fields) {
        $project_trackers          = array_diff($project_trackers, array($tracker));
        $mapped_trackers           = $mapping_fields->getTrackers();
        $this->non_mapped_trackers = array_diff($project_trackers, $mapped_trackers);
        $this->mapped_trackers     = array_diff($mapped_trackers, array($tracker));
    }

    public function getMappedTrackers() {
        return $this->mapped_trackers;
    }

    public function getNonMappedTrackers() {
        return $this->non_mapped_trackers;
    }

}

class Cardwall_OnTop_Config_MappingField {
    private $field;
    private $tracker;
    public function __construct(Tracker $tracker, TrackerTracker_FormElement_Field $field = null) {
        $this->field   = $field;
        $this->tracker = $tracker;
    }
}

class Cardwall_OnTop_Config_MappingFields {
    private $mapping_fields;
    public function __construct(array $mapping_fields) {
        $this->mapping_fields = $mapping_fields;
    }

    public function getTrackers() {
        return array();
    }
}

class Cardwall_OnTop_Config_Column {
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $label;

    /**
     * @param int    $id
     * @param string $label
     */
    public function __construct($id, $label) {
        $this->id      = (int)$id;
        $this->label   = (string)$label;
    }
    
}

class Cardwall_AdminColumnDefinitionView extends Abstract_View {

    /** @var array of Cardwall_OnTop_Config_Column */
    private $columns;
    
    public function __construct(array $columns) {
        parent::__construct();
        $this->columns = $columns;
    }
    
    public function fetchColumnDefinition(Tracker $tracker,
                                          Cardwall_OnTop_Config_MappingFieldValueCollection $mapping_values,
                                          $mappings_factory) {
        $html     = '';
        
        $field    = $tracker->getStatusField();

        if ($field) {
            $html .= '<p>'. 'The column used for the cardwall will be bound to the current status field ('. $this->purify($field->getLabel()) .') of this tracker.' .'</p>';
            $html .= 'TODO: display such columns';
            $html .= '<p>'. 'Maybe you wanna choose your own set of columns?' .'</p>';
        } else {
            $mappings = $mappings_factory->getMappings($tracker);
            $non_mapped_trackers = $mappings_factory->getNonMappedTrackers($tracker);

            
            if (! $this->columns) {
                $html .= '<p>'. 'There is no semantic status defined for this tracker. Therefore you must configure yourself the columns used for cardwall.' .'</p>';
            }
            $html .= '<table><thead><tr valign="bottom">';
            $html .= '<td></td>';
            foreach ($this->columns as $column) {
                $html .= '<td>';
                $html .= '<input type="text" name="column['. $column->id .'][label]" value="'. $this->purify($column->label) .'" />';
                $html .= '</td>';
            }
            $html .= '<td>';
            $html .= '<label>'. 'New column:'. '<br /><input type="text" name="new_column" value="" placeholder="'. 'Eg: On Going' .'" /></label>';
            $html .= '</td>';
            $html .= '<td>'. $this->translate('global', 'btn_delete') .'</td>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            $row_number = 0;
            foreach ($mappings as $mapping) {
                $html .= $this->listExistingMappings($row_number, $mapping, $mapping_values);
                $row_number++;
            }

            $column_count = count($this->columns);
            if ($column_count && $non_mapped_trackers) {
                $html .= $this->addCustomMapping($column_count, $non_mapped_trackers);
            }
            $html .= '</tbody></table>';
        }
        return $html;
    }

    private function listExistingMappings($row_number, $mapping, Cardwall_OnTop_Config_MappingFieldValueCollection $mapping_values) {
        $mapping_tracker = $mapping->tracker;
        $used_sb_fields = $mapping->available_fields;
        $field = $mapping->selected_field;

        $html  = '<tr class="'. html_get_alt_row_color($row_number + 1) .'" valign="top">';
        $html .= '<td>';
        $html .= $this->purify($mapping_tracker->getName()) .'<br />';
        $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][field]">';
        if (!$field) {
            $html .= '<option value="">'. $this->translate('global', 'please_choose_dashed') .'</option>';
        }
        foreach ($used_sb_fields as $sb_field) {
            $selected = $field == $sb_field ? 'selected="selected"' : '';
            $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $this->purify($sb_field->getLabel()) .'</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        foreach ($this->columns as $column) {
            $column_id = $column->id;
            $html .= '<td>';
            if ($field) {
                $field_values = $field->getVisibleValuesPlusNoneIfAny();
                if ($field_values) {
                    $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .'][values]['. $column_id .'][]" multiple="multiple" size="'. count($field_values) .'">';
                    foreach ($field_values as $value) {
                        $selected = $mapping_values->has($field, $value->getId(), $column_id) ? 'selected="selected"' : '';
                        $html .= '<option value="'. $value->getId() .'" '. $selected .'>'. $value->getLabel() .'</option>';
                    }
                    $html .= '</select>';
                } else {
                    $html .= '<em>'. "There isn't any value" .'</em>';
                }
            }
            $html .= '</td>';
        }
        $html .= '<td>';
        $html .= '</td>';
        $html .= '<td>';
        $html .= '<input type="checkbox" name="delete_mapping[]" value="'. (int)$mapping_tracker->getId() .'" />';
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }

    private function addCustomMapping($column_count, $trackers) {
        $colspan = $column_count + 2;
        $html  = '<tr>';
        $html .= '<td colspan="'. $colspan .'">';
        $html .= '<p>Wanna add a custom mapping for one of your trackers? (If no custom mapping, then duck typing on value labels will be used)</p>';
        $html .= '<select name="add_mapping_on">';
        $html .= '<option value="">'. $this->translate('global', 'please_choose_dashed') .'</option>';
        foreach ($trackers as $new_tracker) {
            $html .= '<option value="'. $new_tracker->getId() .'">'. $this->purify($new_tracker->getName()) .'</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }
}


?>
