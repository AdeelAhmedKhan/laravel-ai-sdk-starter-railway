-- pgvector ships in the image; enable it in the application
-- database at first init so vector columns work out of the box.
CREATE EXTENSION IF NOT EXISTS vector;
