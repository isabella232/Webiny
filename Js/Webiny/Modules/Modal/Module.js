import Webiny from 'Webiny';
import Dialog from './Components/Dialog';
import Header from './Components/Header';
import Body from './Components/Body';
import Footer from './Components/Footer';
import Confirmation from './Components/Confirmation';

class Module extends Webiny.Module {

    init() {
        Webiny.Ui.Components.Modal = {
            Dialog,
            Body,
            Header,
            Footer,
            Confirmation
        };
    }
}

export default Module;
