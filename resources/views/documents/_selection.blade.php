<x-posts.selection :post="$document" :id="'document-selection-' . $document->getKey()" route-prefix="documents" :category="$document->category ?: 'Uncategorized'" :date="$document->created_at" />
