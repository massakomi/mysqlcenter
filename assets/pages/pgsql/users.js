import React, {Fragment, useEffect, useState} from "react";
import {msQuery} from "../../functions";
import {Table} from "../../components/Table";

export function PostgresSQLUsers() {

    const loadAll = async () => {
        let data = await msQuery('querysql', {sql: 'SELECT * FROM pg_roles;'})
        setRoles(data)
    }

    const onDelete = (data, event) => {
        event.preventDefault()
        if (!confirm('Подтвердить?')) {
            return false
        }
        msQuery('querysql', {sql: `DROP ROLE ${data.rolname}`})
    }

    const onEdit = (data, event) => {
        event.preventDefault()
        console.log('edit', data)
    }

    // Только роли с атрибутом LOGIN могут использоваться для начального подключения к базе данных.
    // Роль с атрибутом LOGIN можно рассматривать как пользователя базы данных.
    // Альтернатива: CREATE USER имя;
    const onSubmit = (event) => {
        event.preventDefault()
        const name = event.target.querySelector('[name="name"]').value
        let sql = `CREATE ROLE ${name}`
        const password = event.target.querySelector('[name="password"]').value
        if (password) {
            sql += ` PASSWORD '${password}'`
        }
        const connectionLimit = event.target.querySelector('[name="connectionLimit"]').value
        if (connectionLimit) {
            sql += ` CONNECTION LIMIT '${connectionLimit}'`
        }
        const permissions = Array.from(event.target.querySelector('[name="permissions"]').options)
          .filter(option => option.selected)
          .map(option => option.value);
        if (permissions.length) {
            sql += ' ' + permissions.join(' ')
        }
        msQuery('querysql', {sql})
    }

    const [roles, setRoles] = useState([]);
    useEffect(() => {
        loadAll()
    }, []);

    return (
      <Fragment>
          <fieldset className="inline mt-10 mb-10">
              <legend>Создать роль (пользователя) в базе {window.db}</legend>
              <form method="post" onSubmit={onSubmit}>
                  <div className="mb-5">
                      <input name="name" required type="text" className="mr-10"/> имя
                  </div>
                  <div className="mb-5">
                      <input name="password" type="password" className="mr-10"/> пароль
                  </div>
                  <div className="mb-5">
                      <div>
                          Права
                      </div>
                      <select multiple name="permissions">
                          <option value="LOGIN">Право подключения</option>
                          <option value="SUPERUSER">Статус суперпользователя</option>
                          <option value="CREATEDB">Создание базы данных</option>
                          <option value="CREATEROLE">Создание роли</option>
                          <option value="REPLICATION">Запуск репликации</option>
                          <option value="BYPASSRLS">Игнорирование защиты на уровне строк</option>
                      </select>
                  </div>
                  <div className="mb-5">
                      <input name="connectionLimit" type="number" className="mr-10"/> ограничение соединений
                  </div>
                  <button type="submit">Создать!</button>
              </form>
          </fieldset>

          <Table data={roles} onDelete={onDelete} onEdit={onEdit} />

          <div className="mt-10">
              <div>
                  rolcanlogin - могут подключаться к базе данных. Право LOGIN нужно даже для SUPERUSER
              </div>

              <div>
                  <a href="https://postgrespro.ru/docs/postgresql/current/database-roles">Справка</a>
              </div>

          </div>


      </Fragment>
    );
}
