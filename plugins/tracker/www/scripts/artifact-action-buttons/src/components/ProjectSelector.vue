<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <label for="move-artifact-project-selector">
        <translate>Choose project</translate>
        <span class="highlight">*</span>


        <select id="move-artifact-project-selector"
                name="move-artifact-project-selector"
                v-model="selectedProjectId"
            >
            <option disabled="disabled" value="null" selected><translate>Choose project...</translate></option>
            <option v-for="project in sortedProjects"
                    v-bind:key="project.id"
                    v-bind:value="project.id"
            >
                {{ project.label }}
            </option>
        </select>
    </label>
</template>
<script>
import { mapGetters } from "vuex";

export default {
    name: "ProjectSelector",
    computed: {
        ...mapGetters(["sortedProjects"]),
        selectedProjectId: {
            get() {
                return this.$store.state.selected_project_id;
            },
            set(project_id) {
                this.$store.commit("saveSelectedProjectId", project_id);
                this.$store.commit("saveTrackers", []);
                this.$store.commit("resetSelectedTracker");
                this.$store.dispatch("loadTrackerList");
            }
        }
    }
};
</script>
