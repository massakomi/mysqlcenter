import React, {Fragment} from "react";
import {PgRoles} from "./roles";

export function PgDbCreate(props) {
    return (
      <Fragment>
          <span className="flex g0 center">
              <select name="option">
                  <option value="">шаблон</option>
                  <option>template1</option>
                  <option>template0</option>
                  {Object.values(window.databases).map((db) =>
                    <option key={db.toString()}>{db}</option>
                  )}
              </select>
              <img src="/images/help.gif" alt="" title="template0 позволяет получить «чистую» пользовательскую базу данных (в которой никаких пользовательских объектов нет, есть только системные объекты в первозданном виде) + можно скопировать любую другую базу здесь" />
              <a href="https://postgrespro.ru/docs/postgresql/current/manage-ag-templatedbs#MANAGE-AG-TEMPLATEDBS" target="_blank">справка</a>
          </span>

          <PgRoles/>
      </Fragment>
    );
}