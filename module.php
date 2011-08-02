<?php namespace melt\data_tables;

class DataTablesModule extends \melt\Module {
    public static function getAuthor() {
        return "Module maintained by Hannes Landeholm, Melt Software AB. DataTables is a product by Allan Jardine, which is not related to, nor endorse this software in any way.";
    }

    public static function getInfo() {
        return "<b>Integrating DataTables into nanoMVC.</b>";
    }

    public static function getVersion() {
        return "2.0.0";
    }
}