import type { BootableService } from 'src/types/service.types';
import type { ServiceContainer } from './ServiceContainer';
import { Service } from './Service';

export class ExampleService extends Service implements BootableService {
  /**
   * Set up dependencies, or
   * @param container
   */
  register(container: ServiceContainer): void {
    container.addService('exampleBoot', new ExampleService(), true);
  }

  /** Example method */
  test(num: number): void {
    console.log(num);
  }
}
