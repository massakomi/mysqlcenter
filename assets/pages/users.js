import React, {Fragment} from 'react';
import {PostgresSQLUsers} from "./pgsql/users";
import {MysqlUsers} from "./mysql/users";

export function Users() {
    return (
      <Fragment>
          {window.driver === 'mysql' ? <MysqlUsers/> : <PostgresSQLUsers />}
      </Fragment>
    );
}


