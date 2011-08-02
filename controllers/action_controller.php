<?php namespace melt\data_tables;

class ActionController extends \melt\AppController {

    public function batch($list_data) {
        $list_data = @\unserialize(\melt\string\simple_decrypt($list_data));
        if (!\is_array($list_data))
            \melt\request\show_invalid();
        list($interface_name, $model_name, $additional_where_condition, $batch_action) = $list_data;
        $max_instances = 5000;
        $ids = array();
        foreach (\explode(",", (string) @$_POST["ids"], $max_instances) as $id) {
            $id = \intval($id);
            if ($id <= 0 || \array_key_exists($id, $ids))
                continue;
            $ids[$id] = $id;
        }
        $selected_instances = $model_name::dtSelect($interface_name);
        if ($additional_where_condition !== null)
            $selected_instances->and($additional_where_condition);
        $selected_instances->and("id")->isIn($ids);
        $keep_selection = $model_name::dtBatchAction($interface_name, $batch_action, $selected_instances);
        \melt\request\send_json_data(array("success" => true, "deselect" => !$keep_selection));
    }

    public function enlist($list_data) {
        $list_data = @\unserialize(\melt\string\simple_decrypt($list_data));
        if (!\is_array($list_data))
            \melt\request\show_invalid();
        list($interface_name, $model_name, $additional_where_condition) = $list_data;
        // Determine filtering.
        $search = @$_GET['sSearch'];
        $columns = $model_name::dtGetColumns($interface_name);
        $columns_indexed = \array_keys($columns);
        $selection = $model_name::dtSelect($interface_name);
        if (!($selection instanceof \melt\db\SelectQuery))
            \trigger_error("$model_name::dtSelect did not return \melt\db\SelectQuery object as expected!");
        if ($additional_where_condition !== null)
            $selection->and($additional_where_condition);
        $base_selection = clone $selection;
        $max_search_terms = 8;
        $search_terms = \preg_split("#[ ]+#", trim($search), $max_search_terms);
        $search_where = expr();
        // All search terms must match.
        foreach ($search_terms as $term) {
            if ($term == "")
                continue;
            $match_term_condition = $model_name::dtGetSearchCondition($interface_name, $term);
            if (!($match_term_condition instanceof \melt\db\WhereCondition)) {
                $match_term_condition = expr();
                foreach ($columns as $column_name => $column) {
                    if ($column_name[0] == "_")
                        continue;
                    $column_target = (\is_array($column) && \array_key_exists("search", $column))? $column["search"]: $column_name;
                    $match_term_condition->or($column_target)->isContaining($term);
                }
            }
            $search_where->and($match_term_condition);
        }
        $selection->and($search_where);
        // Determine offset and limit.
        $offset = \intval(@$_GET['iDisplayStart']);
        $limit = \intval(@$_GET['iDisplayLength']);
        if ($limit > 100)
            $limit = 100;
        else if ($limit < 0)
            $limit = 0;
        $selection->offset($offset)->limit($limit);
        // Determine sorting order.
        $compare_instance = new $model_name(true);
        $used_cols = array();
        for ($i = 0; ; $i++) {
            // Iterate trough all sorting conditions.
            $col_id_key = "iSortCol_" . $i;
            $sort_dir_key = "sSortDir_" . $i;
            if (!isset($_GET[$col_id_key]))
                break;
            // Get column id (must exist).
            $col_id = \intval($_GET[$col_id_key]) - 1;
            if (!isset($columns_indexed[$col_id]))
                continue;
            // No sorting same column twice.
            if (isset($used_cols[$col_id]))
                continue;
            $used_cols[$col_id] = $col_id;
            // Piece together this sorting condition.
            $col = $columns_indexed[$col_id];
            // No sorting non existing columns.
            if (!$compare_instance->hasField($col))
                continue;
            $sort_dir = @$_GET[$sort_dir_key];
            $sort_dir = (\strlen($sort_dir) > 0 && \strtolower($sort_dir[0]) == "d")? "DESC": "ASC";
            $selection->orderBy($col, $sort_dir);
        }
        // Do the selection. (A special selection where we also count the unbounded rows).
        $found_rows_calc = $selection->countFoundRows();
        $instances = $selection->all();
        // Piece together the JS output.
        $output = array();
        $output["sEcho"] = @$_GET['sEcho'];
        $output["iTotalRecords"] = $base_selection->count();
        $output["iTotalDisplayRecords"] = $found_rows_calc;
        $data = array();
        $first_instance = true;
        foreach ($instances as $id => $instance) {
            $enlist_values = $instance->dtGetValues($interface_name);
            $row = array($id);
            foreach ($columns_indexed as $column_name) {
                if (\is_array($enlist_values) && isset($enlist_values[$column_name])) {
                    $value = $enlist_values[$column_name];
                } else {
                    $value = $column_name[0] !== "_"? (string) $instance->view($column_name): $instance->$column_name;
                }
                $row[] = $value;
            }
            $data[] = $row;
        }
        $output["aaData"] = $data;
        // AJAX Response
        \melt\request\send_json_data($output);
    }
}
