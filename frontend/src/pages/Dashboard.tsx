import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from 'react-query';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import {
  AcademicCapIcon,
  BookOpenIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  UserGroupIcon,
  ChartBarIcon,
  PlayIcon,
  DocumentTextIcon,
} from '@heroicons/react/24/outline';

interface Course {
  id: number;
  code: string;
  title: string;
  description: string;
  category: string;
  credits: number;
  difficulty_level: string;
  total_modules: number;
  enrolled_students_count: number;
}

interface UserStats {
  total_courses: number;
  courses_by_category: Record<string, number>;
  role: string;
  department: string;
  year_level: number;
}

const Dashboard: React.FC = () => {
  const { user } = useAuth();

  // Récupérer les cours de l'utilisateur
  const { data: courses = [] } = useQuery<Course[]>(
    'user-courses',
    async () => {
      const response = await api.get('/api/v1/users/me/courses');
      return response.data;
    },
    {
      enabled: !!user,
    }
  );

  // Récupérer les statistiques de l'utilisateur
  const { data: stats } = useQuery<UserStats>(
    'user-stats',
    async () => {
      const response = await api.get('/api/v1/users/stats/overview');
      return response.data;
    },
    {
      enabled: !!user,
    }
  );

  // Récupérer les derniers posts du forum
  const { data: forumPosts = [] } = useQuery(
    'recent-forum-posts',
    async () => {
      const response = await api.get('/api/v1/forum/posts');
      return response.data.slice(0, 3); // Limiter à 3 posts
    },
    {
      enabled: !!user,
    }
  );

  const getCategoryIcon = (category: string) => {
    const icons: Record<string, React.ComponentType<any>> = {
      embarqué: AcademicCapIcon,
      ia: ChartBarIcon,
      ml: ChartBarIcon,
      deep_learning: ChartBarIcon,
      genie_logiciel: DocumentTextIcon,
    };
    return icons[category] || BookOpenIcon;
  };

  const getCategoryColor = (category: string) => {
    const colors: Record<string, string> = {
      embarqué: 'bg-blue-100 text-blue-800',
      ia: 'bg-purple-100 text-purple-800',
      ml: 'bg-green-100 text-green-800',
      deep_learning: 'bg-red-100 text-red-800',
      genie_logiciel: 'bg-yellow-100 text-yellow-800',
    };
    return colors[category] || 'bg-gray-100 text-gray-800';
  };

  return (
    <div className="space-y-6">
      {/* En-tête du dashboard */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              Bonjour, {user?.full_name} ! 👋
            </h1>
            <p className="text-gray-600 mt-1">
              Bienvenue sur votre plateforme éducative IA
            </p>
          </div>
          <div className="text-right">
            <p className="text-sm text-gray-500">Aujourd'hui</p>
            <p className="text-lg font-semibold text-gray-900">
              {new Date().toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </p>
          </div>
        </div>
      </div>

      {/* Statistiques */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <AcademicCapIcon className="h-8 w-8 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Cours inscrits</p>
              <p className="text-2xl font-bold text-gray-900">{stats?.total_courses || 0}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <BookOpenIcon className="h-8 w-8 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Ressources</p>
              <p className="text-2xl font-bold text-gray-900">12</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <ClockIcon className="h-8 w-8 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Heures d'étude</p>
              <p className="text-2xl font-bold text-gray-900">24h</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <UserGroupIcon className="h-8 w-8 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Étudiants actifs</p>
              <p className="text-2xl font-bold text-gray-900">156</p>
            </div>
          </div>
        </div>
      </div>

      {/* Contenu principal */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Cours récents */}
        <div className="lg:col-span-2">
          <div className="bg-white rounded-lg shadow-sm">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Mes cours</h2>
            </div>
            <div className="p-6">
              {courses.length === 0 ? (
                <div className="text-center py-8">
                  <AcademicCapIcon className="mx-auto h-12 w-12 text-gray-400" />
                  <h3 className="mt-2 text-sm font-medium text-gray-900">Aucun cours</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    Commencez par vous inscrire à un cours.
                  </p>
                  <div className="mt-6">
                    <Link
                      to="/courses"
                      className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                    >
                      Voir les cours
                    </Link>
                  </div>
                </div>
              ) : (
                <div className="space-y-4">
                  {courses.map((course) => {
                    const CategoryIcon = getCategoryIcon(course.category);
                    return (
                      <div
                        key={course.id}
                        className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                      >
                        <div className="flex-shrink-0">
                          <CategoryIcon className="h-8 w-8 text-blue-600" />
                        </div>
                        <div className="ml-4 flex-1">
                          <h3 className="text-sm font-medium text-gray-900">
                            {course.title}
                          </h3>
                          <p className="text-sm text-gray-500">{course.description}</p>
                          <div className="mt-2 flex items-center space-x-4">
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCategoryColor(course.category)}`}>
                              {course.category}
                            </span>
                            <span className="text-xs text-gray-500">
                              {course.total_modules} modules
                            </span>
                          </div>
                        </div>
                        <div className="flex-shrink-0">
                          <Link
                            to={`/courses/${course.id}`}
                            className="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200"
                          >
                            Continuer
                          </Link>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Activité récente */}
        <div className="space-y-6">
          {/* Forum récent */}
          <div className="bg-white rounded-lg shadow-sm">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Forum récent</h2>
            </div>
            <div className="p-6">
              {forumPosts.length === 0 ? (
                <p className="text-sm text-gray-500">Aucune activité récente</p>
              ) : (
                <div className="space-y-4">
                  {forumPosts.map((post: any) => (
                    <div key={post.id} className="border-l-4 border-blue-500 pl-4">
                      <h3 className="text-sm font-medium text-gray-900 line-clamp-2">
                        {post.title}
                      </h3>
                      <p className="text-xs text-gray-500 mt-1">
                        par {post.author_name} • {new Date(post.created_at).toLocaleDateString()}
                      </p>
                    </div>
                  ))}
                </div>
              )}
              <div className="mt-4">
                <Link
                  to="/forum"
                  className="text-sm text-blue-600 hover:text-blue-500"
                >
                  Voir tout →
                </Link>
              </div>
            </div>
          </div>

          {/* Actions rapides */}
          <div className="bg-white rounded-lg shadow-sm">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Actions rapides</h2>
            </div>
            <div className="p-6 space-y-3">
              <Link
                to="/courses"
                className="flex items-center p-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <AcademicCapIcon className="h-5 w-5 mr-3 text-blue-600" />
                Parcourir les cours
              </Link>
              <Link
                to="/resources"
                className="flex items-center p-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <BookOpenIcon className="h-5 w-5 mr-3 text-green-600" />
                Consulter les ressources
              </Link>
              <Link
                to="/forum"
                className="flex items-center p-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <ChatBubbleLeftRightIcon className="h-5 w-5 mr-3 text-purple-600" />
                Participer au forum
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
