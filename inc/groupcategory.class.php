
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

class PluginGroupcategoryGroupcategory extends CommonDBTM
{

    /**
     * All categories
     * @var array
     */
    static private $_all_categories = [];

    /**
     * Get all categories
     *
     * @return array
     */
    public static function getAllCategories()
    {
        if (empty(PluginGroupcategoryGroupcategory::$_all_categories)) {
            $category = new ITILCategory();
            $categories = $category->find([], "completename ASC, level ASC, id ASC");

            self::$_all_categories = $categories;
        }

        return PluginGroupcategoryGroupcategory::$_all_categories;
    }

    /**
     * Get the selected categories for a group
     *
     * @param  Group $group
     * @return array
     */
    public static function getSelectedCategoriesForGroup(Group $group)
    {
        $group_category = new PluginGroupcategoryGroupcategory();

        if ($group_category->getFromDBByCrit(["group_id" => $group->getId()])) {
            $category_ids = explode(', ', $group_category->fields['category_ids']);
            $all_categories = self::getAllCategories();
            $selected_categories = [];
            foreach ($all_categories as $details) {
                if (in_array($details['id'], $category_ids)) {
                    $selected_categories[$details['id']] = $details['completename'];
                }
            }
        } else {
            $selected_categories = [];
        }

        return $selected_categories;
    }

    /**
     * Get the categories for a user
     *
     * @param int $user_id
     * @return array
     */
    public static function getUserCategories($user_id)
    {
        $categories = [
                       '' => '-----'];
        // $category = new ITILCategory();
        // Obtenha todas as categorias
        $all_categories = self::getAllCategories();

        foreach ($all_categories as $cat) {
            // Verifica se a categoria é do nível 1 (categoria pai)
            if ($cat['level'] == 1) {
                $categories[$cat['id']] = $cat['completename'];
            }
        }

        // Log do resultado no log de erros
        error_log('User Categories for user ID ' . $user_id . ': ' . print_r($categories, true));
        return $categories;
    }

    /**
     * Get the categories for a user
     *
     * @param int $user_id
     * @return array
     */
    public static function getUserCategories2($user_id)
    {
        $user_categories = [];

        $user = new User();

        if ($user->getFromDB($user_id)) {
            $user_groups = Group_User::getUserGroups($user_id);

            foreach ($user_groups as $group_data) {
                $group = new Group();
                if ($group->getFromDB($group_data['id'])) {
                    $categories = self::getSelectedCategoriesForGroup($group);
                    $user_categories += $categories;
                }
            }
        }

        return $user_categories;
    }


    public static function getSubcategories($parent_id)
    {
        $subcategories = [];
        $category = new ITILCategory();

        // Busca categorias com nível 2 e que têm o pai especificado
        $all_subcategories = $category->find(['itilcategories_id' => $parent_id], "name ASC, id ASC");

        foreach ($all_subcategories as $subcat) {
            $subcategories[] = [
                'id' => $subcat['id'],
                'text' => $subcat['name']
            ];
        }
        // Log do resultado no log de erros
        error_log('User Categories for user I' . print_r($parent_id, true));
        return $subcategories;
    }

    /**
     * Função para obter a hierarquia de categorias, do filho até o pai.
     *
     * @param int $categoryId ID da categoria inicial
     * @return array Hierarquia de IDs de categoria desde o pai até o último filho
     */
    public static function getCategoryHierarchy($categoryId)
    {
        global $DB;
        $categories = [];

        while ($categoryId) {
            $query = "SELECT id, itilcategories_id FROM glpi_itilcategories WHERE id = '$categoryId' LIMIT 1";
            $result = $DB->query($query);
            if ($row = $DB->fetch_assoc($result)) {
                $categories[] = $row['id'];
                $categoryId = $row['itilcategories_id'];
            } else {
                $categoryId = null; // Encerra o loop se não houver mais pai
            }
        }
        error_log('Category Reverse' . print_r($categories, true));
        return array_reverse($categories); // Reverte a ordem para começar do pai até o último filho
    }



    /**
     * Hook callback when an item is shown
     *
     * @param array $params
     */
    static function post_show_item($params)
    {

        if (!is_array($params['item'])) {
            switch ($params['item']->getType()) {
                case 'Group':
                    plugin_groupcategory_post_show_group($params['item']);
                    break;

                case 'Ticket':
                    plugin_groupcategory_post_show_ticket($params['item']);
                    break;
                default:
                    // nothing to do
            }
        } else {
            // here we are going to view a Solution
            return;
        }
    }

    static function post_item_form($params)
    {
        if (!is_array($params['item'])) {
            switch ($params['item']->getType()) {
                case 'Group':
                    plugin_groupcategory_post_show_group($params['item']);
                    break;

                case 'Ticket':
                    plugin_groupcategory_post_show_ticket($params['item']);
                    break;
                default:
                    // nothing to do
            }
        } else {
            // here we are going to view a Solution
            return;
        }
    }
}
