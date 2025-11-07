/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */

import il from 'ilias';
import document from 'document';
import AsyncRenderer from '../../Core/src/AsyncRenderer.js';
import createProgressBar from './createProgressBar.js';
import createAsyncProgressBar from './createAsyncProgressBar.js';
import GlobalProgressBarSignalDispatcher from './GlobalProgressBarSignalDispatcher.js';
import JQueryEventDispatcher from '../../Core/src/jqueryeventdispatcher.js';

const asyncRenderer = new AsyncRenderer(document);
const signalDispatcher = new GlobalProgressBarSignalDispatcher();

il.UI = il.UI || {};
il.UI.Progress = {};

il.UI.Progress.Bar = {
  indeterminate: (signal, message) => signalDispatcher.indeterminate(signal, message),
  success: (signal, message) => signalDispatcher.success(signal, message),
  failure: (signal, message) => signalDispatcher.failure(signal, message),
  determinate: (signal, progress, message) => signalDispatcher.determinate(
    signal,
    progress,
    message,
  ),
  createAsync: (refreshRateInMs, element, doUpdateSignal, onUpdateSignal) => {
    const asyncProgressBar = createAsyncProgressBar(
      asyncRenderer,
      createProgressBar(JQueryEventDispatcher, document, element, onUpdateSignal),
      element,
      refreshRateInMs,
    );
    signalDispatcher.register(asyncProgressBar, doUpdateSignal);
    return asyncProgressBar;
  },
  create: (element, doUpdateSignal, onUpdateSignal) => {
    const progressBar = createProgressBar(JQueryEventDispatcher, document, element, onUpdateSignal);
    signalDispatcher.register(progressBar, doUpdateSignal);
    return progressBar;
  },
};
