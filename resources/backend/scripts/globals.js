import * as bootstrap from 'bootstrap';
import 'admin-lte/dist/js/adminlte';
import Sortable from 'sortablejs';
import 'inputmask';
import UseBootstrapSelect from 'use-bootstrap-select';
import { Datepicker } from 'vanillajs-datepicker';
import uk from 'vanillajs-datepicker/locales/uk';

Object.assign(Datepicker.locales, uk);

window.bootstrap = bootstrap;
window.Sortable = Sortable;
window.ComboBox = UseBootstrapSelect;
