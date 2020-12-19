<form action="/?s=search" method="post" name="formSearch">
<table class="tableExport">
  <tr>
    <td valign="top">
    <?php echo getTableMultySelector(' name="table[]" multiple class="sel"')?>    <br />
    <a href="#" onClick="msMultiSelect('formSearch', 'table[]', 'select')" class="hs">все</a> &nbsp; 
    <a href="#" onClick="msMultiSelect('formSearch', 'table[]', 'unselect')" class="hs">очистить</a> &nbsp;
    <a href="#" onClick="msMultiSelect('formSearch', 'table[]', 'invert')" class="hs">инверт</a>
    </td>
    <td valign="top">
    искать по всем полям    <br />
    <input name="query" id="queryAll" type="text" size="50" value="<?php echo POST('query')?>" /><br />
	
    искать имя поля    <br />
    <input name="queryField" type="text" size="50" value="<?php echo POST('queryField')?>" /><br />
	
    <input type="submit" value="Искать!" class="submit" />  <br />    
    </td>
  </tr>
</table>
</form>
<script language="JavaScript" type="text/javascript">
document.querySelector('#queryAll').focus()
</script>