<form method="post" action="" class="tableFormEdit">

    имя индекса:
    <input type="text" name="keyName" value="<?php echo POST('keyName')?>"><br /><br />
    тип индекса:
    <?php
    $types = array('PRIMARY KEY','INDEX','UNIQUE','FULLTEXT');
    echo plDrawSelector($types, ' name="keyType"', array_search(POST('keyType'), $types), '', false);
    ?>
    
    <br /><br />
    
    <img src="<?php echo MS_DIR_IMG?>nolines_plus.gif" alt="" border="0" onClick="addRow('tableFormEdit'); return false" title="Добавить поле" style="cursor:pointer " />
    <img src="<?php echo MS_DIR_IMG?>nolines_minus.gif" alt="" border="0" onClick="removeRow('tableFormEdit', 'end'); return false"  title="Удалить поле" style="cursor:pointer "/><br />

    <table id="tableFormEdit">
    <tr>
        <th>Поле</th>
        <th>Размер</th>
    </tr>
    <tr>
        <td><?php echo plDrawSelector($fieldRows, ' name="field[]"') ?></td>
        <td><input type="text" name="length[]" value="" size="10"></td>
    </tr>
    </table>

    <input type="submit" value="Выполнить" class="submit" />
</form>
