```js

// Выписывать сюда новшества из реакт для использования в MSC
// Что я где-то в коде использовал 1-2 раза и просто забываю об этом?

// actionsdb.js - пример получения данных по ajax. Также примеры полезных общих компонентов FieldSet, TableObject

// Пример перебора объекта без for
const trs = Object.values(this.props.data).map((item) => {});
// инлайн прямо в html вывод списков
{Object.values(this.props.tables).map((table) =>
    <option key={table.toString()}>{table}</option>
)}

// Можно забиндить метод прямо в конструктре, чтобы сделать код попроще, не дублировать везде этот бинд
constructor(props) {
    this.setPasswordField2 = this.setPasswordField2.bind(this);
}

// Редко использую обертку всего кода children
props.children

// Условный рендер инлайн
// 1) Вставить элемент в зависимости от условия
{ok && <a />}
// 2) тернарный оператор
{ok ? <a /> : <b />}
// 3) Предотвращение рендеринга компонента
// {ok ? <> : null}
    
// Попробовать применить useState как альтернатива setState для локальной переменной
const [index, setIndex] = useState(0); // 0 это default value
function handleNextClick() {
    setIndex(index + 1);
}

// Вместо обращения к элементу по ид https://react.dev/learn/manipulating-the-dom-with-refs
const inputRef = useRef(null);
inputRef.current.focus();
<input ref={inputRef} />

// Тоже можно. Уже и забыл о чем это. Внимание! use методы можно использовать только внутри Function компонентов (как функции)
React.useEffect(() => {
    // Code here will run after *every* render
});

```
