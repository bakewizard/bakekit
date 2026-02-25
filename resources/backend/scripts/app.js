import '../styles/app.scss';

import './globals.js';

import { initWidgets } from './components/widgets';
import { initAdminMenu } from './components/admin-menu';
import { initConfirmModal } from './components/confirm-modal';
import { initThemeSwitcher } from './components/theme-switcher';
import { initTableCheckboxes } from './components/table-checkboxes';

initWidgets();
initAdminMenu();
initConfirmModal();
initThemeSwitcher();
initTableCheckboxes();
