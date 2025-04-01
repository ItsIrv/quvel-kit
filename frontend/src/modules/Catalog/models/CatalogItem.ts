export interface CatalogItem {
  id?: string; // Optional, depending on whether the item has an ID yet
  user_id: number;
  name: string;
  description: string;
  image: string;
  is_public: boolean;
  metadata: {
    tags: string[];
    rating: number;
  };
  user?: {
    name: string;
  };
  created_at?: string; // Optional, assuming Eloquent adds timestamps
  updated_at?: string; // Optional, assuming Eloquent adds timestamps
}
