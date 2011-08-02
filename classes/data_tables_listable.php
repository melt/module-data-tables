<?php namespace melt\data_tables;

/**
 * Helper interface for standardizing the way models are enlisted.
 */
interface DataTablesListable {
    /**
     * Use this function to specify the columns that should be used
     * when the model is enlisted, and also their labels.
     * The columns can also have the format
     * array("title" => label, "search" => false|target).
     * @param string $interface_name Name of interface.
     * @return array Like: array("column1" => "First Column", "column2" => ...)
     */
    public static function dtGetColumns($interface_name);

    /**
     * Should return a where condition that selects the model instances of
     * this model that matches the search term for the specified
     * interface/enlistment. The where condition should logically
     * correspond to the SQL like Psedu-Expression: Column1 LIKE %$search_term%
     * OR Column2 LIKE %$search_term% OR ... OR ColumnN LIKE %$search_term%
     * If this function returns null, data_tables will construct it's own
     * search expression instead.
     * @return \melt\db\WhereCondition
     */
    public static function dtGetSearchCondition($interface_name, $search_term);

    /**
     * Use this function to display values in an other format when printed
     * in the table. Should return an array where the keys match model fields
     * and their values match their in-table value representation.
     * The model fields not return will be string-ified the normal way.
     * @param string $interface_name Name of interface.
     * @return array Like: array("is_admin" => "NO", ...)
     */
    public function dtGetValues($interface_name);

    /**
     * Use this to define the base selection for table.
     * @param string $interface_name Name of interface.
     * @return \melt\db\SelectQuery
     */
    public static function dtSelect($interface_name);

    /**
     * Callback to execute batch action on given selected array of instances.
     * @param string $batch_action Name of batch action.
     * @param \melt\db\SelectQuery $selected_instances Instances selected for batch action.
     * @return boolean True if client should remain having the instances of the batch selection selected.
     */
    public static function dtBatchAction($interface_name, $batch_action, \melt\db\SelectQuery $selected_instances);
}