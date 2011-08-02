<?php namespace melt\data_tables;

/**
 * Creates a table at this location that enlists the given model.
 * @param string $interface_name The interface name - what you wish to call
 * this enlistment. This is used as a handle in callbacks and for other
 * identification purposes.
 * @param string $model_name Classname of the model that should be listed.
 * The model is required to implement DataTablesListable.
 * @param boolean $batch_operations Array of batch operations that should
 * be supported by enlistment. Every batch operation should be in the format
 * array(identifier => label). If the operation identifier starts with a !
 * a confirmation will be issued before running the operation.
 * The identifier is passed to the dtBatchAction() callback along with the
 * selection of id's.
 * @param string $additional_options Additional options for rendering the
 * data table. These are encoded to json and passed to dataTables when
 * initializing it. They override any options this module sets.
 * See http://www.datatables.net/usage/options for more information.
 * The special option "initCallbackFilter" can be used to create a callback
 * for initializing the initialization option. It should correspond to
 * a function in the global scope which are expected to take the initialization
 * options, modify them and return the new initialization options.
 * This way one can assign javascript closures to the additional options
 * array.
 * @param \melt\db\WhereCondition $additional_where_condition
 * Additional where condition to specify a direct filtering of model instances
 * in the declaration. Additional control over the selection can be gained
 * by overloading dtSelect().
 * @see DataTablesListable
 * @see http://www.datatables.net/usage/
 * @return void
 */
function render_table($interface_name, $model_name, array $batch_operations = array(), array $additional_options = array(), \melt\db\WhereCondition $additional_where_condition = null) {
    assert(\melt\core\implementing($model_name, 'melt\data_tables\DataTablesListable'));
    if ($additional_where_condition instanceof \melt\db\SelectQuery)
        \trigger_error(__FUNCTION__ . " expects argument 5 to be a WhereCondition - you supplied a SelectQuery instead which is too specific and serializes poorly.", \E_USER_ERROR);
    static $included = false;
    if (!$included) {
        \melt\View::render("/data_tables/include", null, false, true);
        $included = true;
    }
    $enlist_url = url("/data_tables/action/enlist/" . \melt\string\simple_crypt(\serialize(array($interface_name, $model_name, $additional_where_condition))));
    $dt_batch_operations = array();
    foreach ($batch_operations as $operation => $label) {
        $confirm = ($operation[0] == "!");
        if ($confirm)
            $operation = \substr($operation, 1);
        $batch_url = url("/data_tables/action/batch/" . \melt\string\simple_crypt(\serialize(array($interface_name, $model_name, $additional_where_condition, $operation))));
        $dt_batch_operations[] = array($batch_url, $label, $confirm);
    }
    $dt_columns = array(array("bSortable" => false, "bSearchable" => false, "sTitle" => ""));
    $columns = $model_name::dtGetColumns($interface_name);
    foreach ($columns as $column)
        $dt_columns[] = array("sTitle" => \is_array($column)? $column["title"]: $column);
    $dt_options = array(
        "aaSorting" => array(array(1, 'asc')),
        "aoColumns" => $dt_columns,
        "bJQueryUI" => true,
        "bProcessing" => true,
        "bServerSide" => true,
        "bPaginate" => true,
        // Using browser built-in table scaler instead.
        "bAutoWidth" => false,
        "bStateSave" => false,
        "sPaginationType" => "full_numbers",
        "iDisplayLength" => 10,
        "oLanguage" => array(
            "oPaginate" => array(
                "sFirst" => _("First Page"),
                "sLast" => _("Last Page"),
                "sNext" => _("Next Page"),
                "sPrevious" => _("Previous Page"),
            ),
            "sEmptyTable" => _("No data available in table"),
            "sInfo" => _("Showing _START_ to _END_ of _TOTAL_ records"),
            "sInfoEmpty" => _("No entries to show"),
            "sInfoFiltered" => _(" (filtered from _MAX_ total records)"),
            "sLengthMenu" => _("Show _MENU_ records"),
            "sProcessing" => _("Please wait..."),
            "sSearch" => _("Search:"),
            "sZeroRecords" => _("No records to display"),
        )
    );
    $dt_options = \array_merge($dt_options, $additional_options);
    return \melt\View::render("/data_tables/table", array("columns" => $columns, "enlist_url" => $enlist_url, "batch_operations" => $dt_batch_operations, "dt_options" => $dt_options), true, false);
}

/**
 * Will insert one or more lines of javascript that refreshes the table
 * identified by it's handle.
 * TODO: delete this function...
 */
function js_table_refresh($table_handle) {
    echo "data_table_$table_handle.fnDraw();";
}