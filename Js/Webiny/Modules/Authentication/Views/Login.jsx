import Webiny from 'Webiny';
const Ui = Webiny.Ui.Components;

class Login extends Webiny.Ui.View {

    constructor(props) {
        super(props);

        this.bindMethods('submit,onSubmit,onSubmitSuccess,renderForm');
    }

    componentWillMount() {
        super.componentWillMount();

        // If already logged in - execute onSuccess
        if (!_.isEmpty(Webiny.Model.get('User'))) {
            if (_.isFunction(this.props.onSuccess)) {
                return this.props.onSuccess();
            }

            Webiny.Router.goToRoute(this.props.onSuccess);
        }
    }

    componentDidMount() {
        super.componentDidMount();
        $('body').addClass('sign-in');
        $('input:first').focus();
    }

    componentWillUnmount() {
        super.componentWillUnmount();
        $('body').removeClass('sign-in');
    }

    renderForm(model, container) {
        return this.props.renderForm.call(this, model, container);
    }

    submit() {
        this.ui('loginForm').submit();
    }

    onSubmit(data, container) {
        this.props.onSubmit.call(this, data, container);
    }

    onSubmitSuccess(data) {
        this.props.onSubmitSuccess.call(this, data);
    }
}

Login.defaultProps = {
    api: '/entities/core/users',
    fields: '*',
    cookieName: 'webiny-token',
    onSubmit(model, container) {
        container.setState({error: null});
        return container.api.post('login', model, {_fields: this.props.fields}).then(apiResponse => {
            if (apiResponse.isError()) {
                return container.setState({error: apiResponse});
            }

            const data = apiResponse.getData();
            Webiny.Cookies.set(this.props.cookieName, data.authToken, {expires: 30, path: '/'});
            Webiny.Model.set({User: data.user});

            this.onSubmitSuccess(data);
        });
    },
    onSubmitSuccess(data) {
        const onSuccess = this.props.onSuccess;
        if (_.isFunction(onSuccess)) {
            return onSuccess(data);
        }
        Webiny.Router.goToRoute(onSuccess || Webiny.Router.getDefaultRoute());
    },
    renderForm(model, container) {
        const passwordProps = {
            type: 'password',
            name: 'password',
            placeholder: 'Password',
            label: 'Password *',
            validate: 'required,password'
            // info: <span className="info-txt"><a tabIndex="-1" href="#">Forgot your password?</a></span>
        };

        return (
            <div className="container">
                <div className="sign-in-holder">
                    <Ui.Form.Loader container={container}/>

                    <div className="form-signin">
                        <a href="#" className="logo">
                            <img src={Webiny.Assets('Core.Backend', 'images/logo_orange.png')} width="180" height="58"/>
                        </a>

                        <h2 className="form-signin-heading"><span></span>Sign in to your Account</h2>

                        <div className="clear"></div>
                        <Ui.Form.Error container={container}/>

                        <div className="clear"></div>
                        <Ui.Input name="username" placeholder="Enter email" label="Email address *" validate="required,email" onEnter={container.submit}/>
                        <Ui.Input {...passwordProps} onEnter={container.submit}/>

                        <div className="form-footer">
                            <div className="submit-wrapper">
                                <Ui.Button type="primary" size="large" onClick={this.submit}>
                                    <span>Submit</span>
                                    <Ui.Icon icon="icon-next"/>
                                </Ui.Button>
                            </div>
                        </div>
                    </div>

                    <p className="copyright">Version 2.0</p>
                    <a href="#" className="site">www.webiny.com</a>
                </div>
            </div>
        );
    },
    renderer() {
        return (
            <Ui.Form.Container api={this.props.api} ui="loginForm" onSubmit={this.onSubmit}>
                {(model, container) => this.renderForm(model, container)}
            </Ui.Form.Container>
        );
    }
};

export default Login;