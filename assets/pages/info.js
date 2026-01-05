import React, {Fragment} from 'react';
import {MysqlInfo} from "./mysql/info";
import {PostgresSQLInfo} from "./pgsql/info";

export function Info(props) {
    return (
      <Fragment>
          {window.driver === 'mysql' ? <MysqlInfo {...props} /> : <PostgresSQLInfo {...props} /> }
      </Fragment>
    );
}
