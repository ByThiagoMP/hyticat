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

if (defined('GLPI_USE_CSRF_CHECK')) {
    $old_GLPI_USE_CSRF_CHECK = GLPI_USE_CSRF_CHECK;
}
define("GLPI_USE_CSRF_CHECK", "0");

require('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['requester_user_id'])) {
    $requester_user_id = (int) trim($_POST['requester_user_id']);
    $user_categories = PluginHytiCatHytiCat::getUserCategories($requester_user_id);

    $results = [];
    foreach ($user_categories as $key => $categorie) {
        $results[] = [
            'id' => $key,
            'text' => $categorie
        ];
    }
    if (!empty($_POST['selectedItilcategoriesId'])) {
        $results = [['id' => $_POST['selectedItilcategoriesId'], 'text' => $user_categories[$_POST['selectedItilcategoriesId']]]] + $results;
    }
    if (count($results)) {
        echo json_encode($results);
    }
}

if (isset($old_GLPI_USE_CSRF_CHECK)) {
    define("GLPI_USE_CSRF_CHECK", $old_GLPI_USE_CSRF_CHECK);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['category_id'])) {
    $parent_id = (int) trim($_POST['category_id']);
    $subcategories = PluginHytiCatHytiCat::getSubcategories($parent_id);

    // Verifica se existem subcategorias e retorna a resposta apropriada
    if (count($subcategories)) {
        echo json_encode($subcategories);
    } else {
        // Retorna um array vazio em JSON se não houver subcategorias
        echo json_encode([]);
    }
}
