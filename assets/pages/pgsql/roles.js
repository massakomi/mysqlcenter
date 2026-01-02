import React, {Fragment, useEffect, useState} from "react";
import {msQuery} from "../../functions";

export function PgRoles() {

    const [roles, setRoles] = useState([]);

    if (roles.length === 0 || window.driver !== 'pgsql') {
        return null
    }

    useEffect(() => {
        msQuery('querysql', {sql: `SELECT * FROM pg_roles WHERE rolcanlogin = TRUE`}, (data) => {
            data = data.filter(function(item, key, arr) {
                return item.rolname !== window.user;
            });
            setRoles(data)
        })
    }, []);

    return (
      <Fragment>
          <select name="user" className="ml-10">
              <option>Для пользователя</option>
              {Object.values(roles).map((role) =>
                <option key={role.toString()}>{role.rolname}</option>
              )}
          </select>
      </Fragment>
    );
}