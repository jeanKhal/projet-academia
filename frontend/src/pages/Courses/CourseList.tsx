import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { BookOpenIcon, ClockIcon, UserGroupIcon, AcademicCapIcon } from '@heroicons/react/24/outline';
import { api } from '../../services/api';

interface Course {
  id: number;
  title: string;
  description: string;
  instructor: string;
  duration: string;
  level: 'beginner' | 'intermediate' | 'advanced';
  category: string;
  enrolled_students: number;
  modules_count: number;
  image_url?: string;
}

const CourseList: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedLevel, setSelectedLevel] = useState('all');

  const { data: courses, isLoading, error } = useQuery<Course[]>('courses', async () => {
    const response = await api.get('/api/v1/courses');
    return response.data;
  });

  const categories = ['all', 'embedded-systems', 'artificial-intelligence', 'machine-learning', 'deep-learning', 'software-engineering'];
  const levels = ['all', 'beginner', 'intermediate', 'advanced'];

  const filteredCourses = courses?.filter(course => {
    const matchesSearch = course.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         course.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = selectedCategory === 'all' || course.category === selectedCategory;
    const matchesLevel = selectedLevel === 'all' || course.level === selectedLevel;
    
    return matchesSearch && matchesCategory && matchesLevel;
  });

  const getCategoryIcon = (category: string) => {
    switch (category) {
      case 'embedded-systems': return '🔧';
      case 'artificial-intelligence': return '🤖';
      case 'machine-learning': return '📊';
      case 'deep-learning': return '🧠';
      case 'software-engineering': return '💻';
      default: return '📚';
    }
  };

  const getLevelColor = (level: string) => {
    switch (level) {
      case 'beginner': return 'bg-green-100 text-green-800';
      case 'intermediate': return 'bg-yellow-100 text-yellow-800';
      case 'advanced': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="text-red-600 text-xl mb-4">Erreur lors du chargement des cours</div>
          <button 
            onClick={() => window.location.reload()} 
            className="btn-primary"
          >
            Réessayer
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Cours Disponibles</h1>
        <p className="text-gray-600">Découvrez nos cours spécialisés en IA, systèmes embarqués et génie logiciel</p>
      </div>

      {/* Filtres et recherche */}
      <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Recherche */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Rechercher un cours
            </label>
            <input
              type="text"
              placeholder="Rechercher par titre ou description..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          {/* Catégorie */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Catégorie
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">Toutes les catégories</option>
              <option value="embedded-systems">Systèmes Embarqués</option>
              <option value="artificial-intelligence">Intelligence Artificielle</option>
              <option value="machine-learning">Machine Learning</option>
              <option value="deep-learning">Deep Learning</option>
              <option value="software-engineering">Génie Logiciel</option>
            </select>
          </div>

          {/* Niveau */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Niveau
            </label>
            <select
              value={selectedLevel}
              onChange={(e) => setSelectedLevel(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">Tous les niveaux</option>
              <option value="beginner">Débutant</option>
              <option value="intermediate">Intermédiaire</option>
              <option value="advanced">Avancé</option>
            </select>
          </div>
        </div>
      </div>

      {/* Résultats */}
      <div className="mb-4">
        <p className="text-gray-600">
          {filteredCourses?.length || 0} cours trouvé{filteredCourses?.length !== 1 ? 's' : ''}
        </p>
      </div>

      {/* Grille des cours */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredCourses?.map((course) => (
          <div key={course.id} className="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-200">
            {/* Image du cours */}
            <div className="h-48 bg-gradient-to-br from-blue-500 to-purple-600 rounded-t-lg flex items-center justify-center">
              <span className="text-6xl">{getCategoryIcon(course.category)}</span>
            </div>

            {/* Contenu du cours */}
            <div className="p-6">
              <div className="flex items-center justify-between mb-3">
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${getLevelColor(course.level)}`}>
                  {course.level === 'beginner' ? 'Débutant' : 
                   course.level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                </span>
                <span className="text-sm text-gray-500">{course.category}</span>
              </div>

              <h3 className="text-xl font-semibold text-gray-900 mb-2">{course.title}</h3>
              <p className="text-gray-600 text-sm mb-4 line-clamp-2">{course.description}</p>

              {/* Statistiques */}
              <div className="flex items-center justify-between text-sm text-gray-500 mb-4">
                <div className="flex items-center">
                  <ClockIcon className="h-4 w-4 mr-1" />
                  {course.duration}
                </div>
                <div className="flex items-center">
                  <BookOpenIcon className="h-4 w-4 mr-1" />
                  {course.modules_count} modules
                </div>
                <div className="flex items-center">
                  <UserGroupIcon className="h-4 w-4 mr-1" />
                  {course.enrolled_students} étudiants
                </div>
              </div>

              {/* Instructeur */}
              <div className="flex items-center mb-4">
                <AcademicCapIcon className="h-4 w-4 text-gray-400 mr-2" />
                <span className="text-sm text-gray-600">{course.instructor}</span>
              </div>

              {/* Boutons d'action */}
              <div className="flex space-x-2">
                <button className="flex-1 btn-primary text-sm py-2">
                  Voir le cours
                </button>
                <button className="btn-secondary text-sm py-2 px-4">
                  S'inscrire
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Message si aucun cours trouvé */}
      {filteredCourses?.length === 0 && (
        <div className="text-center py-12">
          <div className="text-gray-400 text-6xl mb-4">📚</div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">Aucun cours trouvé</h3>
          <p className="text-gray-600">Essayez de modifier vos critères de recherche</p>
        </div>
      )}
    </div>
  );
};

export default CourseList;
