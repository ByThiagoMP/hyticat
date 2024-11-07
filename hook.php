<?php

/**
 * ---------------------------------------------------------------------
 *  hyticat is a plugin to customizes the list of accessible
 *  ticket categories for ticket requesters.
 *  ---------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of hyticat.
 *
 *  hyticat is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  hyticat is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 *  ---------------------------------------------------------------------
 *  @copyright Copyright © 2022-2023 probeSys'
 *  @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 *  ---------------------------------------------------------------------
 */

/**
 * Install the plugin
 *
 * @return boolean
 */
function plugin_hyticat_install()
{
    return true;
}

/**
 * Uninstall the plugin
 *
 * @return boolean
 */
function plugin_hyticat_uninstall()
{
    return true;
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
        $get_user_categories_url = PLUGIN_GROUPCATEGORY_WEB_DIR . '/ajax/get_categories.php';

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
