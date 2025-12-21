class Login extends React.Component {

    constructor(props) {
        super(props);
        this.state = {disabled: true};
    }

    componentDidMount() {
        console.log('mount')
        //document.querySelector('#queryAll').focus()
        // можно стейты назначить и тут, а не в конструкторе, если они все равно приходят
        //this.setState({'query': this.props.query})
    }

    render() {
        return (
          <div>
              login
          </div>
        );
    }
}