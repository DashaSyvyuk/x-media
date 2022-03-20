import { startStimulusApp } from '@symfony/stimulus-bridge';
import home_controller from "./controllers/home_controller";

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.(j|t)sx?$/
));
