import React, {Fragment} from 'react'
import { createRoot } from 'react-dom/client'
import App from './pages/Sql'
import { Actions } from './pages/actions'
import { Config } from './pages/Config'
import {Login} from "./pages/login";
import {Db_compare} from "./pages/db_compare";
import {Db_list} from "./pages/db_list";
import {Export} from "./pages/export";
import {Export_special} from "./pages/export_special";
import {Search} from "./pages/search";
import {SearchTable} from "./pages/searchTable";
import {Tbl_add} from "./pages/tbl_add";
import {Tbl_change} from "./pages/tbl_change";
import {Tbl_compare} from "./pages/tbl_compare";
import {Tbl_data} from "./pages/tbl_data";
import {Tbl_struct_add_key} from "./pages/tbl_struct_add_key";
import {Tbl_list} from "./pages/tbl_list";
import {Tbl_struct} from "./pages/tbl_struct";
import {Tbl_list_structure} from "./pages/tbl_list_structure";
import {HeadTop, Messages, NotFound} from "./components";
import {getComponentByPage} from "./functions";
import {Users} from "./pages/users";
import {Info} from "./pages/info";

let map = new Map();
map.set('Actions', Actions);
map.set('Config', Config);
map.set('Login', Login);
map.set('Db_compare', Db_compare);
map.set('Db_list', Db_list);
map.set('Export', Export);
map.set('ExportSpecial', Export_special);
map.set('Search', Search);
map.set('Info', Info);
map.set('SearchTable', SearchTable);
map.set('Sql', App);
map.set('Tbl_add', Tbl_add);
map.set('Tbl_change', Tbl_change);
map.set('Tbl_compare', Tbl_compare);
map.set('Tbl_data', Tbl_data);
map.set('Tbl_list', Tbl_list);
map.set('Tbl_list_structure', Tbl_list_structure);
map.set('Tbl_struct', Tbl_struct);
map.set('Tbl_struct_add_key', Tbl_struct_add_key);
map.set('Users', Users);
map.set('', NotFound);

const Component = getComponentByPage(map)
const domNode = document.getElementById('root')
const root = createRoot(domNode)
root.render(
  <Fragment>
      <HeadTop />
      <Messages messages={window.messages} />
      <Component {...window.options} />
  </Fragment>
)
