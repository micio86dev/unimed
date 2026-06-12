// Domain types mirroring the Laravel API resources.

export type Difficulty = 'easy' | 'medium' | 'hard'
export type QuestionType = 'single' | 'multiple'
export type AttemptStatus = 'in_progress' | 'completed' | 'abandoned'
export type Role = 'admin' | 'student'

export interface User {
  id: number
  name: string
  email: string
  is_active: boolean
  roles: Role[]
  avatar_url: string | null
  last_login_at: string | null
  created_at: string | null
}

export interface Subject {
  id: number
  name: string
  slug: string
  description: string | null
  color: string
  icon: string | null
  position: number
  questions_count?: number
}

export interface Answer {
  id: number
  text: string
  is_correct?: boolean
  position?: number
}

export interface Question {
  id: number
  subject_id: number
  subject?: Subject
  type: QuestionType
  type_label?: string
  difficulty: Difficulty
  difficulty_label?: string
  text: string
  explanation: string | null
  image_url: string | null
  is_active: boolean
  answers?: Answer[]
  created_at?: string | null
  updated_at?: string | null
}

export interface Quiz {
  id: number
  title: string
  slug: string
  description: string | null
  time_limit_minutes: number | null
  question_count: number
  difficulty: Difficulty | null
  is_published: boolean
  is_auto_generated: boolean
  settings: Record<string, unknown> | null
  questions?: Question[]
  attempts_count?: number
  created_at?: string | null
  updated_at?: string | null
}

export interface AttemptSummary {
  id: number
  quiz_id?: number
  quiz?:
    | Quiz
    | {
        id: number
        title: string
        slug: string
        time_limit_minutes: number | null
        question_count: number
      }
  status: AttemptStatus
  status_label?: string
  total_questions: number
  correct_count: number
  incorrect_count: number
  unanswered_count: number
  percentage: number
  points: number
  time_spent_seconds: number | null
  started_at: string | null
  completed_at: string | null
  created_at?: string | null
}

export interface AttemptQuestion {
  id: number
  position: number
  type: QuestionType
  difficulty: Difficulty
  subject: { id: number; name: string; slug: string; color: string }
  text: string
  image_url: string | null
  answers: { id: number; text: string }[]
  selected_answer_ids: number[]
  is_answered: boolean
}

export interface TakePayload {
  attempt: AttemptSummary
  questions: AttemptQuestion[]
}

export interface ReviewItem {
  position: number
  question: {
    id: number
    text: string
    type: QuestionType
    difficulty: Difficulty
    explanation: string | null
    subject: { id: number; name: string; slug: string; color: string }
  }
  options: { id: number; text: string; is_correct: boolean }[]
  selected_answer_ids: number[]
  correct_answer_ids: number[]
  is_answered: boolean
  is_correct: boolean
}

export interface SubjectBreakdown {
  subject: string
  slug: string
  color: string
  total: number
  correct: number
  accuracy: number
}

export interface ResultPayload {
  attempt: AttemptSummary
  subject_breakdown: SubjectBreakdown[]
  review: ReviewItem[]
}

export interface Ranking {
  position: number | null
  user_id: number
  name?: string
  quizzes_completed: number
  total_points: number
  average_score: number
  best_score: number
  average_time_seconds: number
  last_activity_at: string | null
  is_current_user: boolean
}

export interface StudentAnalytics {
  kpis: {
    completed_quizzes: number
    average_score: number
    best_score: number
    average_time_seconds: number
    total_points: number
  }
  subject_performance: {
    subject: string
    slug: string
    color: string
    total: number
    correct: number
    accuracy: number
  }[]
  recent_attempts: {
    id: number
    quiz: string | null
    quiz_slug: string | null
    percentage: number
    correct_count: number
    total_questions: number
    completed_at: string | null
  }[]
  score_trend: { date: string | null; percentage: number }[]
}

export interface AdminAnalytics {
  kpis: {
    total_users: number
    total_students: number
    active_users: number
    completed_quizzes: number
    average_score: number
    total_questions: number
    total_quizzes: number
  }
  hardest_subjects: {
    subject: string
    slug: string
    color: string
    accuracy: number
    answered: number
  }[]
  attempts_trend: { date: string; count: number }[]
}

export interface Paginated<T> {
  data: T[]
  links: { first: string | null; last: string | null; prev: string | null; next: string | null }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    per_page: number
    to: number | null
    total: number
  }
}
