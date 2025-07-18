import { Course } from './course';
import { Entity } from './entity';
import { TestAttempt } from './test-attempt';
import { TestQuestion } from './test-question';

export type Test = Entity & {
    title: string;
    description?: string;
    type: 'pretest' | 'posttest' | 'delaytest';
    status: 'draft' | 'published';
    duration_in_minutes?: number | null;
    available_from?: string | null;
    available_until?: string | null;
    question?: TestQuestion | null; 
    course_id?: number;
    course?: Course;
    user_latest_completed_attempt?: TestAttempt | null;
    is_locked_by_sequence?: boolean;
    is_locked_by_modules?: boolean;
};