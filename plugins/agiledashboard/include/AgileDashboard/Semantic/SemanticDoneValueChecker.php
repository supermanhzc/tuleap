<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Tracker_FormElement_Field_List_Value;
use Tracker_Semantic_Status;

class SemanticDoneValueChecker
{
    /**
     * @return bool
     */
    public function isValueADoneValue(
        Tracker_FormElement_Field_List_Value $value,
        Tracker_Semantic_Status $semantic_status
    ) {
        return ! in_array($value->getId(), $semantic_status->getOpenValues()) && ! $value->isHidden();
    }
}