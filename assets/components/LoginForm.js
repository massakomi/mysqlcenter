import React from "react";

export default function LoginForm({ currentConfig, update, save }) {
  if (!currentConfig) return null;

  return (
    <form>
      <div>
        <label>Хост</label>
        <input name="host" type="text" value={currentConfig.host ?? ""} onChange={update} />
      </div>

      <div>
        <label>Пользователь</label>
        <input name="user" type="text" value={currentConfig.user ?? ""} onChange={update} />
      </div>

      <div>
        <label>Пароль</label>
        <input name="password" type="password" value={currentConfig.password ?? ""} onChange={update} />
      </div>

      <div>
        <label>Порт</label>
        <input name="port" type="number" value={currentConfig.port ?? ""} onChange={update} />
      </div>

      <div>
        <label>База данных</label>
        <input name="database" type="text" value={currentConfig.database ?? ""} onChange={update} />
      </div>

      <div>
        <label>Драйвер</label>
        <select name="driver" value={currentConfig.driver ?? ""} onChange={update}>
          <option value="mysql">mysql</option>
          <option value="pgsql">pgsql</option>
        </select>
      </div>

      <div>
        <label>Путь к bin папке</label>
        <input name="binPath" type="text" value={currentConfig.binPath ?? ""} onChange={update} />
      </div>

      <div>
        <button onClick={(e) => save("open", e)}>Открыть</button>
        <button onClick={(e) => save("show", e)}>Открыть без привязки</button>
        <button onClick={(e) => save("save", e)}>Сохранить</button>
        <button onClick={(e) => save("check", e)}>Проверить</button>
      </div>
    </form>
  );
}
