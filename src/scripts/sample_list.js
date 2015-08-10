/**
 * @author Ken J. Auberry
 */
$(function(){
  var oTable = $('#sample_table').dataTable({
    "bProcessing": true,
    "bJQueryUI": true,
    "sAjaxSource": base_url + 'ajax/sample_list',
    "aoColumns": [
      { "mData" : "Sample Name" },
      { "mData" : "Sample Description" },
      { "mData" : "Is Hazardous" },
      { "mData" : "Created" },
      { "mData" : "Updated" }
    ]
  });
});
