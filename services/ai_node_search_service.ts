import { ai } from '../modules/ai';
import { ai_search } from '../modules/ai_search';

export class AINodeSearchService {
  private aiModule: typeof ai;
  private searchModule: typeof ai_search;

  constructor() {
    this.aiModule = ai;
    this.searchModule = ai_search;
  }

  /**
   * Convert a rendered node to a vector representation
   */
  async nodeToVector(renderedNode: string): Promise<number[]> {
    return await this.aiModule.embedText(renderedNode);
  }

  /**
   * Search for similar nodes using vector similarity
   */
  async searchSimilarNodes(queryNode: string, limit: number = 10): Promise<any[]> {
    const queryVector = await this.nodeToVector(queryNode);
    return await this.searchModule.vectorSearch(queryVector, limit);
  }

  /**
   * Generate AI response based on rendered node context
   */
  async generateResponse(renderedNode: string, query: string): Promise<string> {
    const context = await this.searchSimilarNodes(renderedNode);
    return await this.aiModule.generateWithContext(query, context);
  }
}

export const aiNodeSearchService = new AINodeSearchService();
