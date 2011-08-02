<?php namespace melt\data_tables; ?>
<div class="data-tables-container" id="<?php echo $this->uuid("container"); ?>">
    <table cellpadding="0" cellspacing="0" class="data_tables display"></table>
    <div class="batch_action_container" style="display: none;">
        <?php echo _("For the <span%s></span> selected entries:", ' class="select_count"'); ?>
        <select></select>
    </div>
    <script type="text/javascript">
        $(function() {
            var current_ids = {};
            var current_ids_count = 0;
            var container_id = <?php echo \json_encode($this->uuid("container")); ?>;
            var enlist_url = <?php echo \json_encode($this->enlist_url); ?>;
            var batch_operations = <?php echo \json_encode($this->batch_operations); ?>;
            var dt_options = <?php echo \json_encode($this->dt_options); ?>;
            var select_option_text = <?php echo \json_encode(_("Select action...")); ?>;
            var confirm_option_text = <?php echo \json_encode(_("Are you sure that you want to perform \"%s\" on the %d selected entries?")); ?>;
            var data_table = null;
            var batch_action_update_fn = function() {
                current_ids_count = 0;
                for (var i in current_ids)
                    current_ids_count++;
                if (current_ids_count > 0) {
                    $("#" + container_id + " .batch_action_container .select_count").text(current_ids_count);
                    $("#" + container_id + " .batch_action_container").fadeIn(600);
                } else {
                    $("#" + container_id + " .batch_action_container").hide();
                }
            };
            if (batch_operations.length > 0) {
                $("#" + container_id + " .batch_action_container select").append(
                    $("<option>").text(select_option_text).attr("value", "").attr("selected", true)
                );
                for (var i = 0; i < batch_operations.length; i++) {
                    var batch_operation = batch_operations[i][0];
                    var label = batch_operations[i][1];
                    var confirm = batch_operations[i][2];
                    $("#" + container_id + " .batch_action_container select").append(
                        $("<option>").text(label).attr("value", batch_operation).data("confirm", confirm)
                    );
                }
                $("#" + container_id + " .batch_action_container select").change(function() {
                    if (current_ids_count == 0)
                        return;
                    var value = $(this).val();
                    if (value == "")
                        return;
                    var option = $("#" + container_id + " .batch_action_container option:selected");
                    $(this).val("");
                    var confirm = $(option).data("confirm");
                    if (confirm) {
                        if (!window.confirm(sprintf(confirm_option_text, $(option).text(), current_ids_count)))
                            return;
                    }
                    var ids = [];
                    for (var current_id in current_ids)
                        ids.push(current_id);
                    $.ajax({
                        url: value,
                        type: "post",
                        data: {
                            ids: ids.join(',')
                        },
                        success: function(response) {
                            if (response && response.deselect) {
                                current_ids = {};
                                batch_action_update_fn();
                            }
                            data_table.fnDraw(false);
                        }
                    });
                });
                dt_options.fnHeaderCallback = function(nHead, aasData, iStart, iEnd, aiDisplay) {
                    if ($(nHead).find(".check_th").length > 0)
                        return;
                    $(nHead).find("th:first-child").addClass("check_th").html(
                        $("<input>").attr({
                            type: "checkbox"
                        }).change(function() {
                            $(this).parents("table:first").find(".check_td input")
                            .attr("checked", $(this).is(":checked")).change();
                            if (!$(this).is(":checked"))
                                current_ids = {};
                            batch_action_update_fn();
                        })
                    );
                };
                dt_options.fnRowCallback = function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                    if ($(nRow).find(".check_td").length == 0) {
                        $(nRow).find("td:first-child").addClass("check_td").html(function() {
                            var id = parseInt(aData[0], 10);
                            $(nRow).data("id", id);
                            return $("<input>").attr({
                                type: "checkbox",
                                name: "c" + id
                            }).change(function() {
                                var id = $(this).attr("name").substr(1);
                                if ($(this).is(":checked"))
                                    current_ids[id] = true;
                                else
                                    delete current_ids[id];
                                batch_action_update_fn();
                            });
                        });
                    }
                    $(nRow).find(".check_td input").each(function() {
                        var id = $(this).attr("name").substr(1);
                        $(this).attr("checked", current_ids[id]);
                    });
                    return nRow;
                };
            } else {
                dt_options.aoColumns[0].bVisible = false;
                dt_options.fnRowCallback = function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                    if ($(nRow).data("id") !== undefined)
                        return nRow;
                    $(nRow).data("id", parseInt(aData[0], 10));
                    return nRow;
                };
            }
            dt_options.fnServerData = function(sSource, aoData, fnCallback) {
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": enlist_url,
                    "data": aoData,
                    "success": fnCallback
                });
            };
            if (dt_options.initCallbackFilter != null)
                dt_options = window[dt_options.initCallbackFilter](dt_options);
            data_table = $("#" + container_id + " table").dataTable(dt_options);
        });
    </script>
</div>