import * as bootstrap from 'bootstrap';
import 'admin-lte/dist/js/adminlte';
import Sortable from 'sortablejs';
import InputMask from 'inputmask';
import ComboBox from 'use-bootstrap-select';
import { Datepicker as DatePicker } from 'vanillajs-datepicker';
import uk from 'vanillajs-datepicker/locales/uk';

import { ajax } from './utils/ajax.js';
import * as animation from './utils/animation.js';
import * as dom from './utils/dom.js';

Object.assign(DatePicker.locales, uk);

window.bootstrap = bootstrap;

window.BakeKit = {
    ajax,
    animation,
    dom,
    ui: {
        ...bootstrap,
        DatePicker,
        ComboBox,
        Sortable,
        InputMask
    },
};
