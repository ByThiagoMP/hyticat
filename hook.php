<?php

/**
 * ---------------------------------------------------------------------
 *  groupcategory is a plugin to customizes the list of accessible
 *  ticket categories for ticket requesters.
 *  ---------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of groupcategory.
 *
 *  groupcategory is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  groupcategory is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 *  ---------------------------------------------------------------------
 *  @copyright Copyright © 2022-2023 probeSys'
 *  @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 *  @link      https://github.com/Probesys/glpi-plugins-groupcategory
 *  @link      https://plugins.glpi-project.org/#/plugin/groupcategory
 *  ---------------------------------------------------------------------
 */

/**
 * Install the plugin
 *
 * @return boolean
 */
function plugin_groupcategory_install()
{
    global $DB;

    if (!$DB->tableExists(getTableForItemType('PluginGroupcategoryGroupcategory'))) {
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS `" . getTableForItemType('PluginGroupcategoryGroupcategory') . "`
            (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `group_id` INT(11) NOT NULL,
                `category_ids` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                INDEX (`group_id`)
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB
        ";
        $DB->query($create_table_query) or die($DB->error());
    }

    return true;
}

/**
 * Uninstall the plugin
 *
 * @return boolean
 */
function plugin_groupcategory_uninstall()
{
    global $DB;

    $tables_to_drop = [
        getTableForItemType('PluginGroupcategoryGroupcategory'),
    ];

    $drop_table_query = "DROP TABLE IF EXISTS `" . implode('`, `', $tables_to_drop) . "`";

    return $DB->query($drop_table_query) or die($DB->error());
}

/**
 * Hook callback when a group is shown
 *
 * @param Group $group
 */
function plugin_groupcategory_post_show_group(Group $group)
{
    if ($group->getId() > 0) {
        $categories = PluginGroupcategoryGroupcategory::getAllCategories();
        $selected_categories = PluginGroupcategoryGroupcategory::getSelectedCategoriesForGroup($group);
        $dom = '';
        $dom .= '<div id="groupcategory_content">';
        $dom .= '<table class="tab_cadre_fixe" >' . "\n";
        $dom .= '<tbody>' . "\n";
        $dom .= '<tr class="tab_bg_1">' . "\n";
        $dom .= '<th colspan="2" class="subheader">';
        $dom .= 'Catégories refusées';
        $dom .= '</th>' . "\n";
        $dom .= '<th colspan="2" class="subheader">';
        $dom .= 'Catégories autorisées';
        $dom .= '</th>' . "\n";
        $dom .= '</tr>' . "\n";
        $dom .= '<tr class="tab_bg_1">' . "\n";
        $dom .= '<td colspan="2">' . "\n";
        $dom .= '<input type="hidden" name="groupcategory_allowed_categories" id="groupcategory_allowed_categories_ids" value="' . implode(', ', array_keys($selected_categories)) . '" />';
        $dom .= '<div>';
        $dom .= '<input type="button" class="submit" id="groupcategory_allow_categories" value="Autoriser >" style="padding: 10px" />';
        $dom .= '</div>' . "\n";
        $dom .= '<select id="groupcategory_denied_categories" style="min-width: 150px; height: 150px; margin-top: 15px;" multiple>' . "\n";

        foreach ($categories as $details) {
            if (!isset($selected_categories[$details['id']])) {
                $dom .= '<option value="' . $details['id'] . '">';
                $dom .= $details['completename'];
                $dom .= '</option>' . "\n";
            }
        }

        $dom .= '</select>' . "\n";
        $dom .= '</td>' . "\n";
        $dom .= '<td colspan="2">' . "\n";
        $dom .= '<div>';
        $dom .= '<input type="button" class="submit" id="groupcategory_deny_categories" value="< Refuser" style="padding: 10px" />';
        $dom .= '</div>' . "\n";
        $dom .= '<select id="groupcategory_allowed_categories" style="min-width: 150px; height: 150px; margin-top: 15px;" multiple>' . "\n";

        foreach ($selected_categories as $category_id => $completename) {
            $dom .= '<option value="' . $category_id . '">';
            $dom .= $completename;
            $dom .= '</option>' . "\n";
        }

        $dom .= '</select>' . "\n";
        $dom .= '</td>' . "\n";
        $dom .= '</tbody>' . "\n";
        $dom .= '</table>' . "\n";
        $dom .= '</div>' . "\n";

        echo $dom;

        $js_block = '
            var _groupcategory_content = $("#groupcategory_content");
            $(_groupcategory_content.html()).detach().insertAfter("div#mainformtable table.tab_cadre_fixe");
            _groupcategory_content.remove();

            var _groupcategory_selected_categories = {
                "denied": [],
                "allowed": []
            };

            var _groupcategory_denied_categories = $("#groupcategory_denied_categories");
            var _groupcategory_allowed_categories = $("#groupcategory_allowed_categories");

            var _groupcategory_allowed_categories_ids_elm = $("#groupcategory_allowed_categories_ids");
            var _groupcategory_allowed_categories_ids = [];

            if (_groupcategory_allowed_categories_ids_elm.val()) {
                _groupcategory_allowed_categories_ids = _groupcategory_allowed_categories_ids_elm.val().split(", ");
            }

            _groupcategory_denied_categories.on("change", function(e) {
                var selection = $(this).val();

                if (selection === null) {
                    selection = [];
                }

                _groupcategory_selected_categories.denied = selection;
            });

            _groupcategory_allowed_categories.on("change", function(e) {
                var selection = $(this).val();

                if (selection === null) {
                    selection = [];
                }

                _groupcategory_selected_categories.allowed = selection;
            });

            $("#groupcategory_allow_categories").on("click", function(e) {
                if (_groupcategory_selected_categories.denied.length) {
                    var
                        current_category_id,
                        current_category_option
                    ;

                    for (var i in _groupcategory_selected_categories.denied) {
                        current_category_id = _groupcategory_selected_categories.denied[i];
                        current_category_option = $("option[value=" + current_category_id + "]", _groupcategory_denied_categories);
                        _groupcategory_allowed_categories.append("<option value=\"" + current_category_id + "\">" + current_category_option.text() + "</option>");
                        current_category_option.remove();

                        _groupcategory_allowed_categories_ids.push(current_category_id);
                    }

                    _groupcategory_allowed_categories_ids_elm.val(_groupcategory_allowed_categories_ids.join(", "));
                }
            });

            $("#groupcategory_deny_categories").on("click", function(e) {
                if (_groupcategory_selected_categories.allowed.length) {
                    var
                        current_category_id,
                        current_category_option,
                        allowed_category_idx
                    ;

                    for (var i in _groupcategory_selected_categories.allowed) {
                        current_category_id = _groupcategory_selected_categories.allowed[i];
                        current_category_option = $("option[value=" + current_category_id + "]", _groupcategory_allowed_categories);
                        _groupcategory_denied_categories.append("<option value=\"" + current_category_id + "\">" + current_category_option.text() + "</option>");
                        current_category_option.remove();

                        allowed_category_idx = _groupcategory_allowed_categories_ids.indexOf(current_category_id);

                        if (allowed_category_idx > -1) {
                            _groupcategory_allowed_categories_ids.splice(allowed_category_idx, 1);
                        }
                    }

                    _groupcategory_allowed_categories_ids_elm.val(_groupcategory_allowed_categories_ids.join(", "));
                }
            });
        ';

        echo Html::scriptBlock($js_block);
    }
}

/**
 * Hook callback before a group is updated
 *
 * @param Group $group
 */
function plugin_groupcategory_group_update(Group $group)
{
    if (isset($group->input['groupcategory_allowed_categories'])) {
        $allowed_categories_ids = trim($group->input['groupcategory_allowed_categories']);

        $selected_categories = PluginGroupcategoryGroupcategory::getSelectedCategoriesForGroup($group);
        $selected_categories_ids = implode(', ', array_keys($selected_categories));

        if ($allowed_categories_ids != $selected_categories_ids) {
            $group_category = new PluginGroupcategoryGroupcategory();
            $exists = $group_category->getFromDBByCrit(["group_id" => $group->getId()]);
            $group_update_params = [
                'group_id' => $group->getId(),
                'category_ids' => $allowed_categories_ids,
            ];

            if ($exists) {
                $group_update_params['id'] = $group_category->getId();
                $group_category->update($group_update_params, [], false);
            } else {
                $group_category->add($group_update_params, [], false);
            }
        }
    }
}

/**
 * Hook callback quando um ticket é mostrado
 *
 * @param Ticket $ticket
 */
function plugin_groupcategory_post_show_ticket(Ticket $ticket)
{
    // Verifica se está na página de edição do ticket
    // $isEditPage = strpos($_SERVER['REQUEST_URI'], '/glpi/ajax/common.tabs.php?_glpi_tab=Ticket%24main&_target=%2Fglpi%2Ffront%2Fticket.form.php&_itemtype=Ticket&id=') !== false;
    $creatTicketSuper = '/glpi/ajax/common.tabs.php?_glpi_tab=Ticket%24main&_target=%2Fglpi%2Ffront%2Fticket.form.php&_itemtype=Ticket&id=0';
    $creatTicketMinal = '/glpi/front/helpdesk.public.php?create_ticket=1';

    error_log('User Categories for user ID  ' . print_r($_SERVER['REQUEST_URI'], true));
    // Só executa o JavaScript se estiver na página de edição
    if ($creatTicketSuper === $_SERVER['REQUEST_URI'] || $creatTicketMinal  === $_SERVER['REQUEST_URI']) {
        global $CFG_GLPI;
        $get_user_categories_url = PLUGIN_GROUPCATEGORY_WEB_DIR . '/ajax/get_user_categories.php';

        $js_block = 'var requester_user_id = ' . $_SESSION['glpiID'] . ';';
        $js_block .= 'var glpi_csrf_token = \'' . Session::getNewCSRFToken() . '\';';
        $js_block .= 'var last_id_dropdown_categorie = null;';

        $selectedItilcategoriesId = isset($ticket->fields['itilcategories_id']) ? $ticket->fields['itilcategories_id'] : '';
        if (isset($ticket->input['itilcategories_id'])) {
            $selectedItilcategoriesId = $ticket->input['itilcategories_id'];
        }

        $js_block .= '
            if (requester_user_id) { 
                loadAllowedCategories(' . $selectedItilcategoriesId . ');
            }

            function loadAllowedCategories(selectedItilcategoriesId) {
                $.ajax("' . $get_user_categories_url . '", {
                    method: "POST",
                    cache: false,
                    data: {
                        requester_user_id: requester_user_id,
                        _glpi_csrf_token: glpi_csrf_token,
                        selectedItilcategoriesId: selectedItilcategoriesId
                    },
                    success: function(response) {
                        if (response.length) {
                            try {
                                var allowed_categories = $.parseJSON(response);
                                displayAllowedCategories(allowed_categories, selectedItilcategoriesId);
                            } catch (e) {
                                console.error("Erro ao analisar a resposta", e);
                            }
                        }
                    },
                    error: function() {
                        console.error("Erro ao carregar categorias permitidas.");
                    }
                });
            }

            function displayAllowedCategories(allowed_categories, selectedItilcategoriesId) {
                var domElementItilcategories = $("select[name=itilcategories_id]");
                var idSelectItil = domElementItilcategories.attr("id");
                
                $("#" + idSelectItil).empty().select2({
                    data: allowed_categories,
                    width: "auto",
                });
                $("#" + idSelectItil).val(selectedItilcategoriesId).trigger("change");

                if (selectedItilcategoriesId == 0) {
                    $("#" + idSelectItil).select2("open");
                }
            }

            $(document).ready(function() {
                function addPriorityDropdown() {
                    var targetContainer = $("#itil-form");

                    if (targetContainer.length) {
                        if ($("#priority_field_container").length === 0) {
                            var priorityDropdownHTML = `
                                <div id="priority_field_container" style="margin-bottom: 10px; display: none;">
                                    <label for="ticket_priority"></label>
                                    <select id="ticket_priority" name="ticket_priority" class="form-select" style="margin-top: 5px;">
                                        <option value="">Selecione uma opção</option>
                                    </select>
                                    <input type="hidden" id="last_selected_category_id" name="itilcategories_id" />
                                </div>
                            `;
                            var categoryField = targetContainer.find("label:contains(\\"Categoria\\")").closest("div").find(".form-select");

                            if (categoryField.length) {
                                categoryField.parent().after(priorityDropdownHTML);
                                
                                categoryField.off("change").on("change", function(event) {
                                    event.preventDefault();
                                    var selectedCategory = $(this).val();
                                    
                                    $(".dynamic-category-container").remove(); // Remove os dropdowns adicionais
                                    
                                    if (selectedCategory) {
                                        $("#priority_field_container").show();
                                        loadPriorityOptions(selectedCategory, $("#priority_field_container"));
                                    } else {
                                        $("#priority_field_container").hide();
                                    }
                                });

                                var selectedItilCategory = sessionStorage.getItem("selectedCategory");
                                if (selectedItilCategory) {
                                    categoryField.val(selectedItilCategory).trigger("change");
                                }
                            }
                        }
                    } else {
                        observeForTicketForm();
                    }
                }

                function loadPriorityOptions(categoryId, container) {
                    $.ajax("' . $get_user_categories_url . '", {
                        method: "POST",
                        cache: false,
                        data: {
                            category_id: categoryId,
                            _glpi_csrf_token: glpi_csrf_token
                        },
                        success: function(response) {
                            var priorityOptions = $.parseJSON(response);

                            container.find("select").empty().append(new Option("Selecione uma opção", ""));

                            $.each(priorityOptions, function(index, option) {
                                container.find("select").append(new Option(option.text, option.id));
                            });

                            container.find("select").off("change").on("change", function() {
                                var selectedPriority = $(this).val();
                                if (selectedPriority) {
                                    createNextDropdown(container, selectedPriority);
                                }
                            });
                        },
                        error: function() {
                            console.error("Erro ao carregar opções de prioridade.");
                        }
                    });
                }

                function createNextDropdown(container, selectedOptionId) {
                    container.nextAll(".dynamic-category-container").remove(); // Remove os dropdowns anteriores

                    $.ajax("' . $get_user_categories_url . '", {
                        method: "POST",
                        cache: false,
                        data: {
                            category_id: selectedOptionId,
                            _glpi_csrf_token: glpi_csrf_token
                        },
                        success: function(response) {
                            var options = $.parseJSON(response);

                            if (options.length) {
                                var newDropdownContainer = $("<div class=\'dynamic-category-container\' style=\'margin-top: 10px;\'></div>");
                                var newDropdown = $("<select class=\'dynamic-category form-select\' style=\'margin-top: 5px;\'></select>");
                                newDropdown.append(new Option("Selecione uma opção", "")); 

                                $.each(options, function(index, option) {
                                    newDropdown.append(new Option(option.text, option.id));
                                });

                                newDropdown.on("change", function() {
                                    var selectedOptionId = $(this).val();
                                    if (selectedOptionId) {
                                        createNextDropdown(newDropdownContainer, selectedOptionId);
                                    }
                                    last_id_dropdown_categorie = selectedOptionId;
                                    $("#last_selected_category_id").val(last_id_dropdown_categorie);
                                });

                                newDropdownContainer.append(newDropdown);
                                container.after(newDropdownContainer);
                            }
                        },
                        error: function() {
                            console.error("Erro ao carregar opções adicionais.");
                        }
                    });
                    last_id_dropdown_categorie = selectedOptionId; //armazena o ultimo id selecionado
                    console.log("armazena o id:", selectedOptionId);
                    $("#last_selected_category_id").val(last_id_dropdown_categorie);
                }

                function observeForTicketForm() {
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === "childList") {
                                var targetContainer = $("#itil-form");
                                if (targetContainer.length) {
                                    observer.disconnect();
                                    addPriorityDropdown();
                                }
                            }
                        });
                    });

                    observer.observe(document.body, { childList: true, subtree: true });
                }

                addPriorityDropdown();
            });
        ';

        echo Html::scriptBlock($js_block);
    }
}
