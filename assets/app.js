import React, {Fragment} from 'react'
import { createRoot } from 'react-dom/client'
import { Sql } from './pages/Sql'
import { Actions } from './pages/actions'
import { Actionsdb } from './pages/Actionsdb'
import { Config } from './pages/Config'
import {Login} from "./pages/login";
import {Db_compare} from "./pages/db_compare";
import {Db_list} from "./pages/db_list";
import {Export} from "./pages/export";
import {ExportSp} from "./pages/exportSp";
import {Search} from "./pages/search";
import {SearchTable} from "./pages/searchTable";
import {Tbl_add} from "./pages/tbl_add";
import {Tbl_change} from "./pages/tbl_change";
import {Tbl_compare} from "./pages/tbl_compare";
import {Tbl_data} from "./pages/tbl_data";
import {Tbl_key_add} from "./pages/tbl_key_add";
import {Tbl_list} from "./pages/tbl_list";
import {Tbl_struct} from "./pages/tbl_struct";
import {Tbl_struct_view} from "./pages/tbl_struct_view";
import {HeadTop, Messages, NotFound} from "./components";
import {getComponentByPage} from "./functions";
import {Users} from "./pages/users";

const ComponentsMap = {
    'Actions': Actions,
    'Actionsdb': Actionsdb,
    'Config': Config,
    'Login': Login,
    'Db_compare': Db_compare,
    'Db_list': Db_list,
    'Export': Export,
    'ExportSp': ExportSp,
    'Search': Search,
    'SearchTable': SearchTable,
    'Sql': Sql,
    'Tbl_add': Tbl_add,
    'Tbl_change': Tbl_change,
    'Tbl_compare': Tbl_compare,
    'Tbl_data': Tbl_data,
    'Tbl_key_add': Tbl_key_add,
    'Tbl_list': Tbl_list,
    'Tbl_struct': Tbl_struct,
    'Tbl_struct_view': Tbl_struct_view,
    'Users': Users,
    'NotFound': NotFound,
}

const Component = getComponentByPage(ComponentsMap)
const domNode = document.getElementById('root')
const root = createRoot(domNode)
root.render(
  <Fragment>
      <HeadTop />
      <Messages messages={window.messages} />
      <Component {...window.options} />
  </Fragment>
)
