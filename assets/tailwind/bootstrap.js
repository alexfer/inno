
import { startStimulusApp } from '@symfony/stimulus-bridge';

const app = startStimulusApp();
//
// // Registers Stimulus controllers from controllers.json and in the controllers/ directory
// export const app = startStimulusApp(require.context(
//     '@symfony/stimulus-bridge/lazy-controller-loader!./controllers.js',
//     true,
//     /\.(j|t)sx?$/
// ));
