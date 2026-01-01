import React, {Fragment, useEffect, useState} from 'react';
import {msQuery} from "../functions";
import {HtmlSelector, Table} from "../components";

export function Users() {
    return (
      <Fragment>
        <MysqlAddUser />
        <MysqlUserInfo />
      </Fragment>
    );
}

function MysqlUserInfo(props) {

    const [users, setUsers] = useState([])
    const [grants, setGrants] = useState([])
    const [privileges, setPrivileges] = useState([])

    const loadAll = async () => {
        let users = await msQuery('querysql', {sql: 'SELECT * FROM mysql.user'})
        setUsers(users)
        let grants = await msQuery('querysql', {sql: 'SHOW GRANTS'})
        setGrants(grants)
        let privileges = await msQuery('querysql', {sql: 'SHOW PRIVILEGES'})
        setPrivileges(privileges)
    }

    useEffect(() => {
        loadAll()
    }, []);

    const onDelete = (row, e) => {
        e.preventDefault()
        msQuery('userDelete', `user=${row.User}`)
    }

    return (
      <Fragment>
          <fieldset className="msGeneralForm">
              <legend>Пользователи</legend>
              <Table data={users} onDelete={onDelete} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW GRANTS</legend>
              <div className="mb-5">Список привилегий, предоставленных аккаунту, который вы используете для соединения с сервером (FOR CURRENT_USER)</div>
              <Table data={grants} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW PRIVILEGES</legend>
              <div className="mb-5">Список системных привилегий, которые поддерживает MySQL сервер. Точный список привилегий зависит от версии вашего сервера.</div>
              <Table data={privileges} />
          </fieldset>
      </Fragment>
    )
}

function MysqlAddUser(props) {

    const [database, setDatabase] = useState('');
    const [passwordField, setPasswordField] = useState('');
    const [password2Field, setPassword2Field] = useState('');
    const [formDisabled, setFormDisabled] = useState(true);
    const requiredPassword = false

    const updateLoginName = event => {
        //setDatabase(event.target.value)
        setFormDisabled(!event.target.value)
    };

    const updatePasswordField = event => {
        setPasswordField(event.target.value)
        let s = event.target.value !== password2Field
        setFormDisabled(s)
    };

    const updatePasswordField2 = event => {
        setPassword2Field(event.target.value)
        let s = passwordField !== event.target.value
        setFormDisabled(s)
    };

    const userAdd = event => {
        event.preventDefault()
        msQuery('userAdd', event.target.closest('form'))
    };


    return (
      <div>
          <fieldset className="msGeneralForm">
              <legend>Добавить пользователя</legend>
              <form onSubmit={userAdd} method="post">
                  <div>Будет создан пользователь и база данных. Пользователю будут выданы все права на эту базу данных. Пароль можно не указывать.</div>
                  <div className="mb-5">
                      <input name="databaseuser" type="text" onKeyUp={updateLoginName} required={true} /> Имя пользователя
                  </div>
                  <div className="mb-5">
                      <HtmlSelector data={window.databases} name="database" auto="false" defaultValue={window.db} /> Выбрать БД, на которую дать права
                  </div>
                  <div className="mb-5">
                      <input name="databaseCreate" type="text" defaultValue={database} /> или создать новую БД
                  </div>
                  <div className="mb-5">
                      <input name="userpass" type="password" required={requiredPassword} onChange={updatePasswordField}/> Пароль
                  </div>
                  <div className="mb-5">
                      <input name="userpass2" type="password" required={requiredPassword} onChange={updatePasswordField2}/> Пароль еще раз
                  </div>
                  <div className="mb-5">
                      <input type="submit" value="Добавить" disabled={formDisabled} />
                  </div>
              </form>
          </fieldset>
      </div>
    );
}