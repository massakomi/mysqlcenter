
Ошибки

--------------------------------------------
MSC задачи - 2021

1 замена строк `?db=${this.props.db}&makeInnodb=1`
2 this.image("arrow_ltr.png")}
image(src) {
return this.props.dirImage + src;
}
3 замена всех <?
4 вынос с php еще код
5 ...options + $pageProps передача
  let options = <?=json_encode($pageProps)?>;
ReactDOM.render(
<App {...options} />,


MSC задачи по коду:
-/+ tbl_edit.htm остался вообще не реализованным + tbl_add также в php папке больше всех
-/+ tbl_data тоже не реализована сама таблица
- db_compare tbl_compare также не реализованы
- tbl_list date2rusString сделать
+ tpl <? убрать php с actions actionsdb и других *.htm файлов (много их)
+ unpkg.com остался в tpl файлах + может вообще в head вынести это
+ вынести <style> из tpl в общий css

MSC задачи - проверка, тесты интерфейса, что работает что нет: TODO

--------------------------------------------
docs/tasks.txt задачи от 2008 года...
