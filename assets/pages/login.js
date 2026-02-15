import React, { useState, useEffect } from "react";
import { msQuery } from "../functions";
import LoginForm from "../components/LoginForm";

const defaults = (name) => ({
  name,
  driver: "mysql",
  host: "",
  user: "",
  password: "",
  port: "3306",
  database: "",
  binPath: "",
});

export function Login({ current: initialCurrent, config: initialConfig }) {
  const [config, setConfig] = useState(
    initialConfig ?? { "0": defaults("Default") }
  );

  const [current, setCurrent] = useState(
    initialCurrent ?? Object.keys(initialConfig ?? { "0": {} })[0]
  );

  // гарантируем существование текущего ключа
  useEffect(() => {
    if (!config[current]) {
      setConfig((prev) => ({
        ...prev,
        [current]: defaults("!error!"),
      }));
    }
  }, [current, config]);

  const changeCurrentSetting = (e) => {
    const newValue = e.target.value;
    if (newValue !== current) {
      setCurrent(newValue);
    }
  };

  const update = (e) => {
    const { name, value } = e.target;

    setConfig((prev) => ({
      ...prev,
      [current]: {
        ...prev[current],
        [name]: value,
      },
    }));
  };

  const add = () => {
    const name = prompt("Введите название");
    if (!name) return;

    const maxKey = Math.max(...Object.keys(config).map(Number));
    const newKey = String(maxKey + 1);

    setConfig((prev) => ({
      ...prev,
      [newKey]: defaults(name),
    }));

    setCurrent(newKey);
  };

  const rename = () => {
    if (!current) {
      alert("Не выбрано ничего");
      return;
    }

    const name = prompt("Введите название", config[current].name);
    if (!name) return;

    setConfig((prev) => ({
      ...prev,
      [current]: {
        ...prev[current],
        name,
      },
    }));
  };

  const save = async (mode, e) => {
    e.preventDefault();

    try {
      const data = await msQuery(mode, {
        config: JSON.stringify(config),
        current,
      });

      if (mode === "open") {
        location.href = "?s=db_list";
      }

      console.log(data);
    } catch (err) {
      console.error("error", err);
    }
  };

  const options = Object.entries(config).map(([key, value]) => (
    <option key={key} value={key}>
      {value.name}
    </option>
  ));

  const currentConfig = config[current];

  if (!currentConfig) return null;

  return (
    <div className="login flex mt-20">
      <LoginForm currentConfig={currentConfig} update={update} save={save} />

      <div className="list">
        <select value={current} size="8" onChange={changeCurrentSetting}>
          {options}
        </select>

        <button onClick={add}>Добавить</button>
        <button onClick={rename}>Переименовать</button>
      </div>
    </div>
  );
}
