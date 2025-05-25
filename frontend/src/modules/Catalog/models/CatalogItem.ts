/**
 * Interface defining the structure of a CatalogItem.
 */
export interface CatalogItem {
  id?: string;
  user_id: number;
  name: string;
  description: string;
  image: string;
  is_public: boolean;
  metadata: {
    tags: string[];
    rating: number;
  } | null;
  user?: {
    name: string;
  };
  created_at?: string;
  updated_at?: string;
}
